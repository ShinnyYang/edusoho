<?php

namespace AppBundle\Controller\AdminV2\Marketing;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\Exception\AccessDeniedException;
use AppBundle\Common\Paginator;
use AppBundle\Controller\AdminV2\BaseController;
use Biz\Classroom\Service\ClassroomService;
use Biz\Coupon\Service\CouponBatchService;
use Biz\Coupon\Service\CouponService;
use Biz\Course\Service\CourseSetService;
use Biz\Goods\Service\GoodsService;
use Biz\System\Service\SettingService;
use Biz\Taxonomy\Service\CategoryService;
use Codeages\Biz\Order\Service\OrderService;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CouponController extends BaseController
{
    public function indexAction(Request $request)
    {
        $conditions = $request->query->all();
        unset($conditions['page']);

        if (isset($conditions['name'])) {
            $conditions['nameLike'] = $conditions['name'];
            unset($conditions['name']);
        }

        $paginator = new Paginator(
            $request,
            $this->getCouponBatchService()->searchBatchsCount($conditions),
            20
        );

        $batchs = $this->getCouponBatchService()->searchBatchs(
            $conditions,
            ['createdTime' => 'DESC'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        foreach ($batchs as $key => &$batch) {
            $batch['couponContent'] = $this->getCouponBatchService()->getCouponBatchContent($batch['id']);
        }

        return $this->render('admin-v2/marketing/coupon/index.html.twig', [
            'batchs' => $batchs,
            'paginator' => $paginator,
        ]);
    }

    public function queryIndexAction(Request $request)
    {
        $conditions = $request->query->all();
        unset($conditions['page']);
        $paginator = new Paginator(
            $request,
            $this->getCouponService()->searchCouponsCount($conditions),
            20
        );

        $coupons = $this->getCouponService()->searchCoupons(
            $conditions,
            ['orderTime' => 'DESC', 'id' => 'ASC'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );
        $batchs = $this->getCouponBatchService()->findBatchsByIds(ArrayToolkit::column($coupons, 'batchId'));
        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($coupons, 'userId'));
        $orders = $this->getOrderService()->findOrdersByIds(ArrayToolkit::column($coupons, 'orderId'));

        return $this->render('admin-v2/marketing/coupon/query.html.twig', [
            'coupons' => $coupons,
            'paginator' => $paginator,
            'batchs' => $batchs,
            'users' => $users,
            'orders' => ArrayToolkit::index($orders, 'id'),
        ]);
    }

    public function exportIndexAction(Request $request)
    {
        $coupons = $this->getCouponService()->searchCoupons(
            $request->query->all(),
            ['orderTime' => 'DESC', 'id' => 'ASC'],
            0,
            PHP_INT_MAX
        );
        $users = $this->getUserService()->searchUsers(['userIds' => array_column($coupons, 'userId') ?: [-1]], [], 0, count($coupons), ['id', 'nickname']);
        $users = ArrayToolkit::index($users, 'id');
        $orders = $this->getOrderService()->searchOrders(['ids' => array_column($coupons, 'orderId') ?: [-1]], [], 0, count($coupons), ['id', 'title', 'price_amount', 'pay_amount']);
        $orders = ArrayToolkit::index($orders, 'id');
        $couponBatch = $this->getCouponBatchService()->findBatchsByIds(array_column($coupons, 'batchId'));

        $coupons = array_map(function ($coupon) use ($users, $orders, $couponBatch) {
            $order = $orders[$coupon['orderId']] ?? [];
            $coupon['rate'] = 'minus' == $coupon['type'] ? $coupon['rate'] : number_format($coupon['rate'], 1);
            $exportCoupon = [
                $coupon['id'],
                $couponBatch[$coupon['batchId']]['codeEnable'] ? $coupon['code'] : '-',
                $this->trans('coupon.target_type.'.$coupon['targetType']),
                $this->convertCouponStatus($coupon['status']),
                $users[$coupon['userId']]['nickname'] ?? '-',
                $order['title'] ?? '-',
                'used' == $coupon['status'] ? $this->trans('coupon.type.'.$coupon['type']).$coupon['rate'].$this->trans("coupon.type.{$coupon['type']}_rate_unit") : '-',
                $order ? $this->get('web.twig.order_extension')->toCash($order['price_amount']) : '-',
                $order ? $this->get('web.twig.order_extension')->toCash($order['pay_amount']) : '-',
                $couponBatch[$coupon['batchId']]['name'] ?? '-',
                $coupon['receiveTime'] ? date('Y-m-d H:i:s', $coupon['receiveTime']) : '-',
                'used' == $coupon['status'] ? date('Y-m-d H:i:s', $coupon['orderTime']) : '-',
            ];

            return implode(',', $exportCoupon);
        }, $coupons);

        $exportFilename = 'coupons'.'-'.date('YmdHi').'.csv';
        $titles = ['编号', '优惠码', '类型', '状态', '使用者', '订单信息', '优惠内容', '原价', '实付', '批次名称', '领取时间', '使用时间'];

        return $this->createExporteCSVResponse($titles, $coupons, $exportFilename);
    }

    public function settingAction(Request $request)
    {
        $couponSetting = $this->getSettingService()->get('coupon', []);

        $default = [
            'enabled' => 1,
        ];

        $couponSetting = array_merge($default, $couponSetting);

        if ('POST' == $request->getMethod()) {
            $couponSetting = $request->request->all();
            if (0 == $couponSetting['enabled']) {
                $inviteSetting = $this->getSettingService()->get('invite', []);
                $inviteSetting['invite_code_setting'] = 0;
                $this->getSettingService()->set('invite', $inviteSetting);
            }
            $this->getSettingService()->set('coupon', $couponSetting);

            $hiddenMenus = $this->getSettingService()->get('menu_hiddens', []);

            if ($couponSetting['enabled']) {
                unset($hiddenMenus['admin_coupon_generate']);
            } else {
                $hiddenMenus['admin_coupon_generate'] = true;
            }

            $this->getSettingService()->set('menu_hiddens', $hiddenMenus);

            $this->getLogService()->info('coupon', 'setting', '更新优惠码状态', $couponSetting);

            return $this->createJsonResponse(true);
        }

        return $this->render('admin-v2/marketing/coupon/setting.html.twig', [
            'couponSetting' => $couponSetting,
        ]);
    }

    public function generateAction(Request $request)
    {
        $couponSetting = $this->getSettingService()->get('coupon', []);

        if (empty($couponSetting['enabled'])) {
            return $this->render('admin-v2/marketing/coupon/permission-message.html.twig', ['type' => 'info']);
        }

        if ('POST' == $request->getMethod()) {
            $couponData = $request->request->all();

            $couponData['rate'] = $couponData['minus-rate'];
            unset($couponData['minus-rate']);

            $batch = $this->getCouponBatchService()->generateCoupon($couponData);

            $data = [
                'code' => true,
                'message' => '',
                'url' => $this->generateUrl('admin_v2_coupon_batch_create', ['batchId' => $batch['id']]),
                'num' => $batch['generatedNum'],
            ];

            return $this->createJsonResponse($data);
        }

        return $this->render('admin-v2/marketing/coupon/generate.html.twig');
    }

    public function batchCreateAction(Request $request, $batchId)
    {
        $batch = $this->getCouponBatchService()->getBatch($batchId);

        $generateNum = $request->request->get('generateNum', 0);
        if ($generateNum >= 1000) {
            return $this->createJsonResponse(['code' => false, 'message' => 'GenerateNum must be less than 1000']);
        }

        $this->getCouponBatchService()->createBatchCoupons($batch['id'], $generateNum);

        $generatedNum = $this->getCouponService()->searchCouponsCount(['batchId' => $batch['id']]);

        $data = [
            'code' => true,
            'url' => $this->generateUrl('admin_v2_coupon_batch_create', ['batchId' => $batch['id']]),
            'generatedNum' => $generatedNum,
            'percent' => ceil($generatedNum / $batch['generatedNum'] * 100),
            'goto' => '',
        ];

        if ($generatedNum >= $batch['generatedNum']) {
            $data['goto'] = $this->generateUrl('admin_v2_coupon');
        }

        return $this->createJsonResponse($data);
    }

    public function checkPrefixAction(Request $request)
    {
        $prefix = $request->query->get('value');
        $result = $this->getCouponBatchService()->checkBatchPrefix($prefix);

        if ($result) {
            $response = ['success' => true, 'message' => '该前缀可以使用'];
        } else {
            $response = ['success' => false, 'message' => '该前缀已存在'];
        }

        return $this->createJsonResponse($response);
    }

    public function deleteAction(Request $request, $id)
    {
        $result = $this->getCouponBatchService()->deleteBatch($id);

        return $this->createJsonResponse(true);
    }

    public function exportCsvAction($batchId)
    {
        $batch = $this->getCouponBatchService()->getBatch($batchId);

        $coupons = $this->getCouponService()->searchCoupons(
            ['batchId' => $batchId],
            ['orderTime' => 'DESC', 'id' => 'ASC'],
            0,
            $batch['generatedNum']
        );
        $users = $this->getUserService()->searchUsers(['userIds' => array_column($coupons, 'userId') ?: [-1]], [], 0, count($coupons), ['id', 'nickname']);
        $users = ArrayToolkit::index($users, 'id');
        $orders = $this->getOrderService()->searchOrders(['ids' => array_column($coupons, 'orderId') ?: [-1]], [], 0, count($coupons), ['id', 'title', 'price_amount', 'pay_amount']);
        $orders = ArrayToolkit::index($orders, 'id');

        $coupons = array_map(function ($coupon) use ($users, $orders, $batch) {
            $order = $orders[$coupon['orderId']] ?? [];
            $exportCoupon = [
                $coupon['batchId'],
                $coupon['id'],
                $batch['codeEnable'] ? $coupon['code'] : '-',
                $this->convertCouponStatus($coupon['status']),
                empty($coupon['deadline']) ? '--' : date('Y-m-d', $coupon['deadline']),
                $users[$coupon['userId']]['nickname'] ?? '-',
                $order['title'] ?? '-',
                $order ? $this->get('web.twig.order_extension')->toCash($order['price_amount']) : '-',
                $order ? $this->get('web.twig.order_extension')->toCash($order['pay_amount']) : '-',
                $coupon['receiveTime'] ? date('Y-m-d H:i:s', $coupon['receiveTime']) : '-',
                'used' == $coupon['status'] ? date('Y-m-d H:i:s', $coupon['orderTime']) : '-',
            ];

            return implode(',', $exportCoupon);
        }, $coupons);

        $exportFilename = 'couponBatch-'.$batchId.'-'.date('YmdHi').'.csv';

        $titles = ['批次', '编号', '优惠码', '状态', '有效期至', '使用者', '订单信息', '原价', '实付', '领取时间', '使用时间'];

        return $this->createExporteCSVResponse($titles, $coupons, $exportFilename);
    }

    public function detailAction(Request $request, $batchId)
    {
        $count = $this->getCouponService()->searchCouponsCount(['batchId' => $batchId]);

        $batch = $this->getCouponBatchService()->getBatch($batchId);

        $paginator = new Paginator($this->get('request'), $count, 20);

        $coupons = $this->getCouponService()->searchCoupons(
            ['batchId' => $batchId],
            ['orderTime' => 'DESC', 'id' => 'ASC'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );
        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($coupons, 'userId'));

        $orders = $this->getOrderService()->findOrdersByIds(ArrayToolkit::column($coupons, 'orderId'));

        return $this->render('admin-v2/marketing/coupon/coupon-modal.html.twig', [
            'coupons' => $coupons,
            'batch' => $batch,
            'paginator' => $paginator,
            'users' => $users,
            'orders' => ArrayToolkit::index($orders, 'id'),
        ]);
    }

    public function targetDetailAction(Request $request, $targetType, $batchId)
    {
        $batch = $this->getCouponBatchService()->getBatch($batchId);
        $paginator = new Paginator($this->get('request'), count($batch['targetIds']), 10);
        $targetIds = empty($batch['targetIds']) ? [-1] : $batch['targetIds'];

        $targets = [];
        $users = [];
        $categories = [];
        if ('course' === $targetType) {
            $targets = $this->getCourseSetService()->searchCourseSets(
                ['ids' => $targetIds],
                ['createdTime' => 'ASC'],
                $paginator->getOffsetCount(),
                $paginator->getPerPageCount()
            );
            $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($targets, 'creator'));
        } elseif ('classroom' === $targetType) {
            $targets = $this->getClassroomService()->searchClassrooms(
                ['classroomIds' => $targetIds],
                ['createdTime' => 'ASC'],
                $paginator->getOffsetCount(),
                $paginator->getPerPageCount()
            );
            $categories = $this->getCategoryService()->findCategoriesByIds(ArrayToolkit::column($targets, 'categoryId'));
        } elseif ('goods' === $targetType) {
            $targets = $this->getGoodsService()->searchGoods(
                ['ids' => $targetIds],
                ['createdTime' => 'DESC'],
                $paginator->getOffsetCount(),
                $paginator->getPerPageCount()
            );
        }

        return $this->render('admin-v2/marketing/coupon/target-modal.html.twig', [
            'targets' => $targets,
            'targetType' => $targetType,
            'users' => $users,
            'categories' => $categories,
            'paginator' => $paginator,
        ]);
    }

    public function getReceiveUrlAction(Request $request, $batchId)
    {
        $batch = $this->getCouponBatchService()->getBatch($batchId);

        return $this->render('admin-v2/marketing/coupon/get-receive-url-modal.html.twig', [
            'batch' => $batch,
            'url' => $this->generateUrl('coupon_receive', ['token' => $batch['token']], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }

    public function couponReceiveAction(Request $request, $token)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            $goto = $this->generateUrl('coupon_receive', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

            return $this->redirect($this->generateUrl('login', ['goto' => $goto]));
        }
        $couponBatch = $this->getCouponBatchService()->getBatchByToken($token);
        if (!$couponBatch['linkEnable']) {
            throw new AccessDeniedException('Coupon receipt by link is not allowed');
        }
        $result = $this->getCouponBatchService()->receiveCoupon($token, $user['id']);

        if ($result['code']) {
            if (isset($result['id'])) {
                $response = $this->redirect($this->generateUrl('my_cards', ['cardType' => 'coupon', 'cardId' => $result['id']]));

                $response->headers->setCookie(new Cookie('modalOpened', '1'));

                return $response;
            }

            return $this->createMessageResponse('info', $result['message'], '', 3, $this->generateUrl('my_cards', ['cardType' => 'coupon']));
        }

        return $this->createMessageResponse('info', '无效的链接', '', 3, $this->generateUrl('homepage'));
    }

    private function createExporteCSVResponse(array $header, array $data, $outputFilename)
    {
        $content = implode(',', $header)."\r\n";
        $content .= implode("\r\n", $data);
        $content = chr(239).chr(187).chr(191).$content;
        $response = new Response();
        $response->headers->set('Content-type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$outputFilename.'"');
        $response->headers->set('Content-length', strlen($content));
        $response->setContent($content);

        return $response;
    }

    protected function convertCouponStatus($status)
    {
        $statusMap = [
            'unused' => '未使用',
            'receive' => '已领取',
            'used' => '已使用',
        ];

        return $statusMap[$status];
    }

    /**
     * @return OrderService
     */
    private function getOrderService()
    {
        return $this->createService('Order:OrderService');
    }

    /**
     * @return CouponService
     */
    private function getCouponService()
    {
        return $this->createService('Coupon:CouponService');
    }

    /**
     * @return CouponBatchService
     */
    private function getCouponBatchService()
    {
        return $this->createService('Coupon:CouponBatchService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    /**
     * @return GoodsService
     */
    protected function getGoodsService()
    {
        return $this->createService('Goods:GoodsService');
    }

    /**
     * @return CategoryService
     */
    private function getCategoryService()
    {
        return $this->createService('Taxonomy:CategoryService');
    }

    /**
     * @return CourseSetService
     */
    private function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    /**
     * @return ClassroomService
     */
    private function getClassroomService()
    {
        return $this->createService('Classroom:ClassroomService');
    }
}
