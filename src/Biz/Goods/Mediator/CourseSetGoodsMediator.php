<?php

namespace Biz\Goods\Mediator;

use AppBundle\Common\ArrayToolkit;
use Biz\Course\Service\CourseService;
use Biz\Goods\GoodsException;

class CourseSetGoodsMediator extends AbstractGoodsMediator
{
    /**
     * @var string[] 标记哪些字段可以通用更新
     */
    public $normalFields = [
        'title',
        'subtitle',
        'summary',
        'orgId',
        'orgCode',
        'maxRate',
    ];

    public function onCreate($courseSet)
    {
        $product = $this->getProductService()->createProduct([
            'targetType' => 'course',
            'targetId' => $courseSet['id'],
            'title' => $courseSet['title'],
            'owner' => $courseSet['creator'],
        ]);

        $goods = $this->getGoodsService()->createGoods([
            'type' => 'course',
            'productId' => $product['id'],
            'title' => $courseSet['title'],
            'subtitle' => $courseSet['subtitle'],
            'creator' => $courseSet['creator'],
        ]);

        return [$product, $goods];
    }

    public function onUpdateNormalData($courseSet)
    {
        list($product, $goods) = $this->getProductAndGoods($courseSet);

        $product = $this->getProductService()->updateProduct($product['id'], [
            'title' => $courseSet['title'],
        ]);

        $goods = $this->getGoodsService()->updateGoods($goods['id'], [
            'title' => $courseSet['title'],
            'subtitle' => $courseSet['subtitle'],
            'summary' => $courseSet['summary'],
            'images' => $courseSet['cover'],
            'orgId' => $courseSet['orgId'],
            'orgCode' => $courseSet['orgCode'],
            'categoryId' => $courseSet['categoryId'],
            'maxRate' => $courseSet['maxRate'],
            'discountId' => $courseSet['discountId'],
            'discountType' => $courseSet['discountType'],
        ]);

        return [$product, $goods];
    }

    public function onClose($courseSet)
    {
        list($product, $goods) = $this->getProductAndGoods($courseSet);

        $goods = $this->getGoodsService()->unpublishGoods($goods['id']);

        return [$product, $goods];
    }

    public function onPublish($courseSet)
    {
        list($product, $goods) = $this->getProductAndGoods($courseSet);

        $goods = $this->getGoodsService()->publishGoods($goods['id']);

        return [$product, $goods];
    }

    /**
     * @param $courseSet
     * 删除课程的同时触发商品的删除，同时删除规格
     */
    public function onDelete($courseSet)
    {
        $existProduct = $this->getProductService()->getProductByTargetIdAndType($courseSet['id'], 'course');
        if (empty($existProduct)) {
            return;
        }
        $this->getProductService()->deleteProduct($existProduct['id']);

        $existGoods = $this->getGoodsService()->getGoodsByProductId($existProduct['id']);
        if (empty($existGoods)) {
            return;
        }
        $goodsSpecs = $this->getGoodsService()->findGoodsSpecsByGoodsId($existGoods['id']);
        foreach ($goodsSpecs as $goodsSpec) {
            $this->getGoodsService()->deleteGoodsSpecs($goodsSpec['id']);
        }

        $this->getGoodsService()->deleteGoods($existGoods['id']);
    }

    public function onSortGoodsSpecs($courseSet)
    {
        list($product, $goods) = $this->getProductAndGoods($courseSet);
        $specs = $this->getGoodsService()->findGoodsSpecsByGoodsId($goods['id']);
        $targetIds = ArrayToolkit::column($specs, 'targetId');
        $courses = ArrayToolkit::index($this->getCourseService()->findCoursesByIds($targetIds), 'id');
        foreach ($specs as $spec) {
            if (isset($courses[$spec['targetId']])) {
                $this->getGoodsService()->updateGoodsSpecs($spec['id'], ['seq' => $courses[$spec['targetId']]['seq']]);
            }
        }

        return [$product, $goods];
    }

    /**
     * @param $courseSet
     *
     * @return array|mixed
     *                     推荐商品，设置权重
     */
    public function onRecommended($courseSet)
    {
        list($product, $goods) = $this->getProductAndGoods($courseSet);
        $goods = $this->getGoodsService()->recommendGoods($goods['id'], $courseSet['recommendedSeq']);

        return [$product, $goods];
    }

    /**
     * @param $courseSet
     *
     * @return array|mixed
     *                     取消推荐商品
     */
    public function onCancelRecommended($courseSet)
    {
        list($product, $goods) = $this->getProductAndGoods($courseSet);
        $goods = $this->getGoodsService()->cancelRecommendGoods($goods['id']);

        return [$product, $goods];
    }

    public function onMaxRateChange($courseSet)
    {
        list($product, $goods) = $this->getProductAndGoods($courseSet);
        $goods = $this->getGoodsService()->changeGoodsMaxRate($goods['id'], $courseSet['maxRate']);

        return [$product, $goods];
    }

    protected function getProductAndGoods($courseSet)
    {
        $existProduct = $this->getProductService()->getProductByTargetIdAndType($courseSet['id'], 'course');
        if (empty($existProduct)) {
            return [[], []];
        }

        $existGoods = $this->getGoodsService()->getGoodsByProductId($existProduct['id']);
        if (empty($existGoods)) {
            throw GoodsException::GOODS_NOT_FOUND();
        }

        return [$existProduct, $existGoods];
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->biz->service('Course:CourseService');
    }
}
