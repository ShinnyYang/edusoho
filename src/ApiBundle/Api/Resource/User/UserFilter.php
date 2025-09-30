<?php

namespace ApiBundle\Api\Resource\User;

use ApiBundle\Api\Resource\Filter;
use ApiBundle\Api\Util\AssetHelper;

class UserFilter extends Filter
{
    protected $simpleFields = [
        'id', 'nickname', 'title', 'smallAvatar', 'mediumAvatar', 'largeAvatar', 'uuid', 'destroyed', 'weChatQrCode', 'showable',
    ];

    protected $publicFields = [
        'about', 'faceRegistered',
    ];

    protected $authenticatedFields = [
        'email', 'locale', 'uri', 'type', 'roles', 'promotedSeq', 'locked', 'currentIp', 'gender', 'iam', 'city', 'qq', 'signature', 'company',
        'job', 'school', 'class', 'weibo', 'weixin', 'isQQPublic', 'isWeixinPublic', 'isWeiboPublic', 'following', 'follower', 'verifiedMobile',
        'promotedTime', 'lastPasswordFailTime', 'loginTime', 'approvalTime', 'vip', 'token', 'havePayPassword', 'fingerPrintSetting', 'weChatQrCode',
        'aiAgentToken',
    ];

    protected $mode = self::SIMPLE_MODE;

    protected function simpleFields(&$data)
    {
        $this->transformAvatar($data);
        $this->destroyedNicknameFilter($data);
    }

    protected function publicFields(&$data)
    {
        if (!isset($data['about'])) {
            return;
        }
        $data['about'] = $this->convertAbsoluteUrl($data['about']);
    }

    protected function authenticatedFields(&$data)
    {
        $data['promotedTime'] = date('c', $data['promotedTime']);
        $data['lastPasswordFailTime'] = date('c', $data['lastPasswordFailTime']);
        $data['loginTime'] = date('c', $data['loginTime']);
        $data['approvalTime'] = date('c', $data['approvalTime']);
        $data['email'] = '*****';
        if (!empty($data['verifiedMobile'])) {
            $data['verifiedMobile'] = substr_replace($data['verifiedMobile'], '****', 3, 4);
        } else {
            unset($data['verifiedMobile']);
        }
    }

    private function transformAvatar(&$data)
    {
        $data['smallAvatar'] = AssetHelper::getFurl($data['smallAvatar'], 'avatar.png');
        $data['mediumAvatar'] = AssetHelper::getFurl($data['mediumAvatar'], 'avatar.png');
        $data['largeAvatar'] = AssetHelper::getFurl($data['largeAvatar'], 'avatar.png');
        $data['weChatQrCode'] = !empty($data['weChatQrCode']) ? AssetHelper::getFurl($data['weChatQrCode'], 'avatar.png') : '';
        $data['avatar'] = [
            'small' => $data['smallAvatar'],
            'middle' => $data['mediumAvatar'],
            'large' => $data['largeAvatar'],
        ];

        unset($data['smallAvatar']);
        unset($data['mediumAvatar']);
        unset($data['largeAvatar']);
    }

    protected function destroyedNicknameFilter(&$data)
    {
        $data['nickname'] = (1 == $data['destroyed']) ? '帐号已注销' : $data['nickname'];
    }
}
