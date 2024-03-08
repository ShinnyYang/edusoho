<?php

namespace Tests\Unit\User\Service;

namespace Biz\User\Service\Impl;

use AppBundle\Common\ReflectionUtils;
use AppBundle\Common\SimpleValidator;
use Biz\BaseTestCase;
use Biz\User\CurrentUser;

// TODO

class AuthServiceTest extends BaseTestCase
{
    public function testRegisterWithTypeDefault()
    {
        $value = ['register_mode' => 'default'];
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register([
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ]);
        $this->assertEquals($user['email'], 'test@edusoho.com');
    }

    public function testRegisterLimitValidator()
    {
        $currentUser = new CurrentUser();
        $currentUser->fromArray([
            'id' => 2,
            'nickname' => 'admin1',
            'email' => 'admin3@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => ['ROLE_USER'],
        ]);
        $this->getServiceKernel()->setCurrentUser($currentUser);
        $value = ['register_mode' => 'default', 'register_protective' => 'middle'];
        $this->getSettingService()->set('auth', $value);
        $service = $this->getAuthService();
        for ($i = 1; $i <= AuthServiceImpl::MID_IP_MAX_ALLOW_ATTEMPT_ONE_DAY; ++$i) {
            $result = ReflectionUtils::invokeMethod($service, 'registerLimitValidator', [['createdIp' => '127.0.0.1']]);
            $this->assertFalse($result);
        }
        $result = ReflectionUtils::invokeMethod($service, 'registerLimitValidator', [['createdIp' => '127.0.0.1']]);
        $this->assertTrue($result);
    }

    public function testProtectiveRule()
    {
        $service = $this->getAuthService();
        for ($i = 0; $i < AuthServiceImpl::MID_IP_MAX_ALLOW_ATTEMPT_ONE_DAY; ++$i) {
            $result = ReflectionUtils::invokeMethod($service, 'protectiveRule', ['middle', '127.0.0.1']);
            $this->assertTrue($result);
        }
        $result = ReflectionUtils::invokeMethod($service, 'protectiveRule', ['middle', '127.0.0.1']);
        $this->assertFalse($result);

        for ($i = 0; $i < AuthServiceImpl::HIGH_IP_MAX_ALLOW_ATTEMPT_ONE_HOUR; ++$i) {
            $result = ReflectionUtils::invokeMethod($service, 'protectiveRule', ['high', '127.0.0.1']);
            $this->assertTrue($result);
        }
        $result = ReflectionUtils::invokeMethod($service, 'protectiveRule', ['high', '127.0.0.1']);
        $this->assertFalse($result);
    }

    public function testSyncLoginWithDefaultAuthProvider()
    {
        $this->mockBiz(
            'User:UserService',
            [
                [
                    'functionName' => 'getUserBindByTypeAndUserId',
                    'returnValue' => [],
                    'withParams' => ['default', 2],
                    'runTimes' => 1,
                ],
                [
                    'functionName' => 'getUserBindByTypeAndUserId',
                    'returnValue' => ['id' => 2, 'fromId' => 2],
                    'withParams' => ['default', 2],
                    'runTimes' => 1,
                ],
            ]
        );
        $result = $this->getAuthService()->syncLogin(2);
        $this->assertEquals('', $result);

        $result = $this->getAuthService()->syncLogin(2);
        $this->assertTrue($result);
    }

    public function testSyncLogoutWithDefaultAuthProvider()
    {
        $this->mockBiz(
            'User:UserService',
            [
                [
                    'functionName' => 'getUserBindByTypeAndUserId',
                    'returnValue' => [],
                    'withParams' => ['default', 2],
                    'runTimes' => 1,
                ],
                [
                    'functionName' => 'getUserBindByTypeAndUserId',
                    'returnValue' => ['id' => 2, 'fromId' => 2],
                    'withParams' => ['default', 2],
                    'runTimes' => 1,
                ],
            ]
        );
        $result = $this->getAuthService()->syncLogout(2);
        $this->assertEquals('', $result);

        $result = $this->getAuthService()->syncLogout(2);
        $this->assertTrue($result);
    }

    public function testChangeNickname()
    {
        $value = ['register_mode' => 'default'];
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register([
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ]);

        $this->getAuthService()->changeNickname($user['id'], 'newName');
        $newUser = $this->getUserService()->getUser($user['id']);
        $this->assertEquals('newName', $newUser['nickname']);
    }

    public function testChangeEmail()
    {
        $value = ['register_mode' => 'default'];
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register([
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ]);

        $this->getAuthService()->changeEmail($user['id'], '123456', 'newemail@edusoho.com');
        $newUser = $this->getUserService()->getUser($user['id']);
        $this->assertEquals('newemail@edusoho.com', $newUser['email']);
    }

    public function testChangePassword()
    {
        $value = ['register_mode' => 'default'];
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register([
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ]);

        $this->getAuthService()->changePassword($user['id'], '123456', '654321');
        $newUser = $this->getUserService()->getUser($user['id']);
        $this->assertNotEquals($user['password'], $newUser['password']);
    }

    public function testChangePayPassword()
    {
        $value = ['register_mode' => 'default'];
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register([
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ]);

        $this->getAuthService()->changePayPassword($user['id'], '123456', '930919');
        $newUser = $this->getUserService()->getUser($user['id']);
        $this->assertNotEquals($user['payPassword'], $newUser['payPassword']);
    }

    public function testChangePayPasswordWithoutLoginPassword()
    {
        $value = ['register_mode' => 'default'];
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register([
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ]);

        $this->getAuthService()->changePayPasswordWithoutLoginPassword($user['id'], '930919');
        $newUser = $this->getUserService()->getUser($user['id']);
        $this->assertNotEquals($user['payPassword'], $newUser['payPassword']);
    }

    /**
     * @expectedException \Biz\User\UserException
     */
    public function testChangePayPasswordWithErrorPassword()
    {
        $this->mockBiz(
            'User:UserService',
            [
                [
                    'functionName' => 'verifyPassword',
                    'returnValue' => false,
                    'withParams' => [2, 'password'],
                ],
            ]
        );
        $this->getAuthService()->changePayPassword(2, 'password', 'newPassword');
    }

    public function testRefillFormDataWithoutNicknameAndEmail()
    {
        $value = ['register_mode' => 'email_or_mobile'];
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register([
            'password' => '123456',
            'emailOrMobile' => '18989492142',
            'nickname' => 'testuser',
        ]);
        $this->assertNotNull($user);
        $this->getSettingService()->delete('auth');
    }

    public function testCheckUserNameWithUnexistName()
    {
        $result = $this->getAuthService()->checkUserName('testUsername');
        $this->assertEquals('success', $result[0]);
        $this->assertEquals('', $result[1]);
    }

    public function testCheckUserNameWithExistName()
    {
        $value = ['register_mode' => 'default'];
        $this->getSettingService()->set('auth', $value);
        $this->getAuthService()->register([
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ]);

        $this->getAuthService()->checkUserName('test');
        // $this->assertEquals('error_duplicate', $result[0]);
        // $this->assertEquals('名称已存在!', $result[1]);
    }

    public function testCheckUserNameWithNumNickname()
    {
        $result = SimpleValidator::nickname('11111111111');
        $this->assertEquals(false, $result);
    }

    public function testCheckUserNameWithWrongUserName()
    {
        $result = $this->getAuthService()->checkUserName('🦌');
        $this->assertEquals(['error_mismatching', '用户名不合法!'], $result);
    }

    public function testCheckEmailWithUnexistEmail()
    {
        $result = $this->getAuthService()->checkEmail('test@yeah.net');
        $this->assertEquals('success', $result[0]);
        $this->assertEquals('', $result[1]);
    }

    public function testCheckEmailWithExistEmail()
    {
        $value = ['register_mode' => 'default'];
        $this->getSettingService()->set('auth', $value);
        $this->getAuthService()->register([
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ]);

        $this->getAuthService()->checkEmail('test@edusoho.com');
        // $this->assertEquals('error_duplicate', $result[0]);
        // $this->assertEquals('Email已存在!', $result[1]);
    }

    public function testCheckMobileWithUnexistMobile()
    {
        $this->getAuthService()->checkMobile('18989492142');
        // $this->assertEquals('success', $result[0]);
        // $this->assertEquals('', $result[1]);
    }

    public function testCheckMobileWithExistMobile()
    {
        $value = ['register_mode' => 'mobile'];
        $this->getSettingService()->set('auth', $value);
        $this->getAuthService()->register([
            'password' => '123456',
            'mobile' => '18989492142',
            'nickname' => 'test',
        ]);
        $result = $this->getAuthService()->checkMobile('18989492142');
        // $this->assertEquals('error_duplicate', $result[0]);
        // $this->assertEquals('手机号码已存在!', $result[1]);
        $this->getSettingService()->delete('auth');
    }

    public function testCheckEmailOrMobileWithUnexistEmailOrMobile()
    {
        $result = $this->getAuthService()->checkEmailOrMobile('18989492142');
        // $this->assertEquals('success', $result[0]);
        // $this->assertEquals('', $result[1]);
    }

    public function testCheckEmailOrMobileWithExistMobile()
    {
        $value = ['register_mode' => 'email_or_mobile'];
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register([
            'password' => '123456',
            'emailOrMobile' => '18989492142',
            'nickname' => 'test',
        ]);
        $result = $this->getAuthService()->checkEmailOrMobile('18989492142');
        // $this->assertEquals('error_duplicate', $result[0]);
        // $this->assertEquals('手机号码已存在!', $result[1]);
        $this->getSettingService()->delete('auth');
    }

    public function testCheckEmailOrMobileWithExistEmail()
    {
        $value = ['register_mode' => 'email_or_mobile'];
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register([
            'password' => '123456',
            'emailOrMobile' => 'test@edusoho.com',
            'nickname' => 'test',
        ]);
        $result = $this->getAuthService()->checkEmailOrMobile('test@edusoho.com');
        // $this->assertEquals('error_duplicate', $result[0]);
        // $this->assertEquals('Email已存在!', $result[1]);
        $this->getSettingService()->delete('auth');
    }

    public function testCheckEmailOrMobileWithErrorEmail()
    {
        $value = ['register_mode' => 'email_or_mobile'];
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register([
            'password' => '123456',
            'emailOrMobile' => '18989492142',
            'nickname' => 'test',
        ]);
        $result = $this->getAuthService()->checkEmailOrMobile('1898949');
        $this->assertEquals('error_dateInput', $result[0]);
        $this->getSettingService()->delete('auth');
    }

    /*
     * @group current
     */
    public function testCheckPasswordByTrue()
    {
        $value = ['register_mode' => 'default'];
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register([
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ]);

        $result = $this->getAuthService()->checkPassword($user['id'], '123456');
        $this->assertTrue($result);
    }

    public function testChangePasswordByFalse()
    {
        $value = ['register_mode' => 'default'];
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register([
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '12456',
        ]);

        $result = $this->getAuthService()->checkPassword($user['id'], '123456');
        $this->assertFalse($result);
    }

    public function testCheckPayPasswordByTrue()
    {
        $value = ['register_mode' => 'default'];
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register([
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ]);
        $this->getAuthService()->changePayPasswordWithoutLoginPassword($user['id'], '123456');
        $result = $this->getAuthService()->checkPayPassword($user['id'], '123456');
        $this->assertTrue($result);
    }

    public function testCheckPayPasswordByFalse()
    {
        $value = ['register_mode' => 'default'];
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register([
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ]);
        $this->getAuthService()->changePayPasswordWithoutLoginPassword($user['id'], '123456');
        $result = $this->getAuthService()->checkPayPassword($user['id'], '654321');
        $this->assertFalse($result);
    }

    /* 以下的带有partner的都需要访问Discuz等的API，默认default 返回false */
    public function testCheckPartnerLoginById()
    {
        $value = ['register_mode' => 'default'];
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register([
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ]);

        $result = $this->getAuthService()->checkPartnerLoginById($user['id'], '123456');
        $this->assertFalse($result);
    }

    public function testCheckPartnerLoginByNickname()
    {
        $value = ['register_mode' => 'default'];
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register([
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ]);

        $result = $this->getAuthService()->checkPartnerLoginByNickname($user['id'], 'test');
        $this->assertFalse($result);
    }

    public function testCheckPartnerLoginByEmail()
    {
        $value = ['register_mode' => 'default'];
        $this->getSettingService()->set('auth', $value);
        $user = $this->getAuthService()->register([
            'email' => 'test@edusoho.com',
            'nickname' => 'test',
            'password' => '123456',
        ]);

        $result = $this->getAuthService()->checkPartnerLoginByEmail($user['id'], 'test@edusoho.com');
        $this->assertFalse($result);
    }

    public function testGetPartnerAvatar()
    {
        $this->mockBiz(
            'User:UserService',
            [
                [
                    'functionName' => 'getUserBindByTypeAndUserId',
                    'returnValue' => [],
                    'withParams' => ['default', 2],
                ],
            ]
        );
        $result = $this->getAuthService()->getPartnerAvatar(2);
        $this->assertNull($result);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetPartnerAvatarWithBind()
    {
        $this->mockBiz(
            'User:UserService',
            [
                [
                    'functionName' => 'getUserBindByTypeAndUserId',
                    'returnValue' => ['id' => 2, 'fromId' => 2],
                    'withParams' => ['default', 2],
                ],
            ]
        );
        $result = $this->getAuthService()->getPartnerAvatar(2);
    }

    public function testGetPartnerName()
    {
        $result = $this->getAuthService()->getPartnerName();
        $this->assertEquals('default', $result);
    }

    public function testIsRegisterEnabledWithOtherTypeByTrue()
    {
        $value = ['register_mode' => 'email_or_mobile', 'register_enabled' => 'open'];
        $this->getSettingService()->set('auth', $value);
        $result = $this->getAuthService()->isRegisterEnabled();
        $this->assertTrue($result);
        $this->getSettingService()->delete('auth');
    }

    public function testIsRegisterEnabledWithOtherTypeByFalse()
    {
        $value = ['register_mode' => 'testNotTrue'];
        $this->getSettingService()->set('auth', $value);
        $result = $this->getAuthService()->isRegisterEnabled();
        $this->assertFalse($result);
        $this->getSettingService()->delete('auth');
    }

    public function testIsRegisterEnabledWithDefaultType()
    {
        $this->getSettingService()->delete('auth');
        $result = $this->getAuthService()->isRegisterEnabled();
        $this->assertTrue($result);
    }

    /**
     * @expectedException \Codeages\Biz\Framework\Service\Exception\InvalidArgumentException
     */
    public function testGetAuthProviderWithErrorMode()
    {
        ReflectionUtils::setProperty($this->getAuthService(), 'partner', null);
        $value = ['mode' => 'testNotTrue'];
        $this->getSettingService()->set('user_partner', $value);
        $this->getAuthService()->getPartnerName();
        $this->assertFalse(true);
    }

    protected function getAuthService()
    {
        return $this->createService('User:AuthService');
    }

    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }
}
