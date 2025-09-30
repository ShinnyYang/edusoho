<?php

namespace AppBundle\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;
use Topxia\Service\Common\ServiceKernel;

class LogoutSuccessHandler extends DefaultLogoutSuccessHandler
{
    public function onLogoutSuccess(Request $request)
    {
        $goto = $request->query->get('goto');

        if (!$goto) {
            if ($this->isMicroMessenger($request) && $this->isWeixinEnabled()) {
                $goto = 'homepage';
            } else {
                $goto = 'login';
            }
        }

        $this->targetUrl = $this->httpUtils->generateUri($request, $goto);

        if ($this->checkWebsite($request, $this->targetUrl)) {
            $this->targetUrl = $this->httpUtils->generateUri($request, 'homepage');
        }

        setcookie('is_skip_mobile_bind', 0, -1);

        setcookie('_last_logout_locale', $request->getSession()->get('_locale'), -1);
        // setcookie("U_LOGIN_TOKEN", '', -1);
        return parent::onLogoutSuccess($request);
    }

    protected function isWeixinEnabled()
    {
        $setting = $this->getSettingService()->get('login_bind');

        return isset($setting['enabled']) && isset($setting['weixinmob_enabled']) && $setting['enabled'] && $setting['weixinmob_enabled'];
    }

    protected function checkWebsite($request, $targetUrl)
    {
        $hostUrl = $request->getUriForPath('');
        if (0 === strpos($targetUrl, $hostUrl)) {
            return false;
        } else {
            return true;
        }
    }

    private function getAuthService()
    {
        return ServiceKernel::instance()->createService('User:AuthService');
    }

    public function isMicroMessenger($request)
    {
        return false !== strpos($request->headers->get('User-Agent'), 'MicroMessenger');
    }

    protected function getSettingService()
    {
        return ServiceKernel::instance()->createService('System:SettingService');
    }
}
