<?php

namespace ApiBundle\Api\Resource\Good;

use ApiBundle\Api\Resource\Filter;
use ApiBundle\Api\Resource\User\UserFilter;
use ApiBundle\Api\Util\AssetHelper;
use AppBundle\Common\ServiceToolkit;
use VipPlugin\Api\Resource\VipLevel\VipLevelFilter;

class GoodSpecsFilter extends Filter
{
    protected $simpleFields = [
        'id', 'goodsId', 'targetId', 'title', 'seq', 'status',
        'price', 'priceObj', 'displayPrice', 'displayPriceObj',
        'coinPrice', 'usageMode', 'usageDays', 'usageStartTime',
        'usageEndTime', 'showable', 'buyable', 'buyableStartTime',
        'buyableEndTime', 'buyableMode', 'maxJoinNum', 'services',
        'isMember', 'learnUrl', 'vipLevelInfo', 'vipUser', 'teachers',
        'access', 'canVipJoin', 'hasCertificate', 'taskDisplay', 'hidePrice',
    ];

    protected $publicFields = [
        'id', 'goodsId', 'targetId', 'title', 'images', 'seq', 'status',
        'price', 'priceObj', 'displayPrice', 'displayPriceObj', 'coinPrice',
        'usageMode', 'usageDays', 'usageStartTime', 'usageEndTime', 'showable',
        'buyable', 'buyableStartTime', 'buyableEndTime', 'buyableMode', 'maxJoinNum',
        'services', 'isMember', 'learnUrl', 'vipLevelInfo', 'vipUser', 'teachers',
        'access', 'canVipJoin', 'hasCertificate', 'taskDisplay',
    ];

    protected function simpleFields(&$data)
    {
        $this->transTime($data);
        $this->transServices($data['services']);
        $userFilter = new UserFilter();
        $userFilter->setMode(Filter::SIMPLE_MODE);
        $userFilter->filters($data['teachers']);
        if (!empty($data['vipLevelInfo'])) {
            $vipLevelFilter = new VipLevelFilter();
            $vipLevelFilter->setMode(Filter::SIMPLE_MODE);
            $vipLevelFilter->filter($data['vipLevelInfo']);
        }
    }

    protected function publicFields(&$data)
    {
        $this->transTime($data);
        $this->transServices($data['services']);
        $this->transformImages($data['images']);
        $userFilter = new UserFilter();
        $userFilter->setMode(Filter::SIMPLE_MODE);
        $userFilter->filters($data['teachers']);
        if (!empty($data['vipLevelInfo'])) {
            $vipLevelFilter = new VipLevelFilter();
            $vipLevelFilter->setMode(Filter::PUBLIC_MODE);
            $vipLevelFilter->filter($data['vipLevelInfo']);
        }
    }

    private function transTime(&$specs)
    {
        $specs['buyableStartTime'] = empty($specs['buyableStartTime']) ? '0' : date('c', $specs['buyableStartTime']);
        $specs['buyableEndTime'] = empty($specs['buyableEndTime']) ? '0' : date('c', $specs['buyableEndTime']);
    }

    private function transServices(&$services)
    {
        if (empty($services)) {
            return [];
        }
        $services = AssetHelper::callAppExtensionMethod('transServiceTags', [ServiceToolkit::getServicesByCodes($services)]);
    }

    private function transformImages(&$images)
    {
        $images['small'] = AssetHelper::getFurl(empty($images['small']) ? '' : $images['small'], 'course.png');
        $images['middle'] = AssetHelper::getFurl(empty($images['middle']) ? '' : $images['middle'], 'course.png');
        $images['large'] = AssetHelper::getFurl(empty($images['large']) ? '' : $images['large'], 'course.png');
    }
}
