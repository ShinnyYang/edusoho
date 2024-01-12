<?php

namespace Tests\Unit\User\Dao;

use Tests\Unit\Base\BaseDaoTestCase;

class UserDaoTest extends BaseDaoTestCase
{
    public function testGetByEmail()
    {
        $defaultUser = $this->getDefaultMockFields();
        $this->getUserDao()->create($defaultUser);
        $user = $this->getUserDao()->getByEmail($defaultUser['email']);

        $this->assertNotNull($user);
        $this->assertEquals($defaultUser['id'], $user['id']);
    }

    public function testGetUserByType()
    {
        $defaultUser = $this->getDefaultMockFields();
        $this->getUserDao()->create($defaultUser);
        $user = $this->getUserDao()->getUserByType($defaultUser['type']);

        $this->assertNotNull($user);
        $this->assertEquals($defaultUser['id'], $user['id']);
    }

    public function testGetByNickname()
    {
        $defaultUser = $this->getDefaultMockFields();
        $this->getUserDao()->create($defaultUser);
        $user = $this->getUserDao()->getByNickname($defaultUser['nickname']);

        $this->assertNotNull($user);
        $this->assertEquals($defaultUser['id'], $user['id']);
    }

    public function testCountByMobileNotEmpty()
    {
        $defaultUser = $this->getDefaultMockFields();
        $defaultUser['type'] = 'default';
        $this->getUserDao()->create($defaultUser);
        $this->assertEquals(0, $this->getUserDao()->countByMobileNotEmpty());
        $this->getUserProfileDao()->create([
            'id' => '3',
            'mobile' => '13967340627',
        ]);

        $this->assertEquals(1, $this->getUserDao()->countByMobileNotEmpty());
    }

    public function testFindUnlockedUsersWithMobile()
    {
        $defaultUser = $this->getDefaultMockFields();
        $defaultUser['type'] = 'default';
        $this->getUserDao()->create($defaultUser);
        $this->assertEmpty($this->getUserDao()->findUnlockedUsersWithMobile(0, 10));
        $this->getUserProfileDao()->create([
            'id' => '3',
            'mobile' => '13967340627',
        ]);
        $this->assertNotEmpty($this->getUserDao()->findUnlockedUsersWithMobile(0, 10));
    }

    public function testGetByVerifiedMobile()
    {
        $defaultUser = $this->getDefaultMockFields();
        $defaultUser['verifiedMobile'] = '13967340627';
        $this->getUserDao()->create($defaultUser);
        $user = $this->getUserDao()->getByVerifiedMobile('13967340627');
        $this->assertEquals($defaultUser['id'], $user['id']);

        $user = $this->getUserDao()->getByVerifiedMobile('13967340628');
        $this->assertEmpty($user);
    }

    public function testCountByLessThanCreatedTime()
    {
        $time = time();
        $defaultUser = $this->getDefaultMockFields();
        $defaultUser['type'] = 'default';
        $this->getUserDao()->create($defaultUser);

        $this->assertEquals(2, $this->getUserDao()->countByLessThanCreatedTime($time));
        $this->assertEquals(0, $this->getUserDao()->countByLessThanCreatedTime($time - 10));
    }

    public function testFindByNicknames()
    {
        $defaultUser = $this->getDefaultMockFields();
        $this->getUserDao()->create($defaultUser);
        $this->getUserDao()->create([
            'id' => '5',
            'nickname' => 'test2',
            'roles' => ['ROLE_ADMIN'],
            'password' => '3DMYb8GyEXk32ruFzw4lxy2elz6/aoPtA5X8vCTWezg=',
            'salt' => 'qunt972ow5c48k4wc8k0ss448os0oko',
            'email' => '800@qq.com',
            'type' => 'default',
            'uuid' => $this->getUserService()->generateUUID(),
        ]);

        $users = $this->getUserDao()->findByNicknames(['test', 'test2']);
        $this->assertEquals(2, count($users));

        $users = $this->getUserDao()->findByNicknames(['test']);
        $this->assertEquals(1, count($users));
    }

    public function testFindByIds()
    {
        $defaultUser = $this->getDefaultMockFields();
        $this->getUserDao()->create($defaultUser);
        $this->getUserDao()->create([
            'id' => '5',
            'nickname' => 'test2',
            'roles' => ['ROLE_ADMIN'],
            'password' => '3DMYb8GyEXk32ruFzw4lxy2elz6/aoPtA5X8vCTWezg=',
            'salt' => 'qunt972ow5c48k4wc8k0ss448os0oko',
            'email' => '800@qq.com',
            'type' => 'default',
            'uuid' => $this->getUserService()->generateUUID(),
        ]);

        $users = $this->getUserDao()->findByIds([3, 5]);

        $this->assertCount(2, $users);
    }

    public function testGetByInviteCode()
    {
        $defaultUser = $this->getDefaultMockFields();
        $this->getUserDao()->create($defaultUser);

        $user = $this->getUserDao()->getByInviteCode($defaultUser['inviteCode']);

        $this->assertNotNull($user);
        $this->assertEquals($defaultUser['id'], $user['id']);
    }

    public function testWaveCounterById()
    {
        $defaultUser = $this->getDefaultMockFields();
        $this->getUserDao()->create($defaultUser);

        $this->getUserDao()->waveCounterById(3, 'newMessageNum', 2);
        $this->getUserDao()->waveCounterById(3, 'newNotificationNum', 5);

        $user = $this->getUserDao()->get($defaultUser['id']);
        $this->assertEquals(2, $user['newMessageNum']);
        $this->assertEquals(5, $user['newNotificationNum']);
    }

    public function testDeleteCounterById()
    {
        $defaultUser = $this->getDefaultMockFields();
        $defaultUser['newMessageNum'] = 3;
        $defaultUser['newNotificationNum'] = 6;

        $this->getUserDao()->create($defaultUser);

        $this->getUserDao()->deleteCounterById(3, 'newMessageNum');
        $this->getUserDao()->deleteCounterById(3, 'newNotificationNum');

        $user = $this->getUserDao()->get($defaultUser['id']);
        $this->assertEquals(0, $user['newMessageNum']);
        $this->assertEquals(0, $user['newNotificationNum']);
    }

    public function testAnalysisRegisterDataByTime()
    {
        $time = time();
        $defaultUser = $this->getDefaultMockFields();
        $defaultUser['type'] = 'default';
        $this->getUserDao()->create($defaultUser);
        $result = $this->getUserDao()->analysisRegisterDataByTime($time - 20, $time + 20);
        $this->assertEquals(2, $result[0]['count']);

        $result = $this->getUserDao()->analysisRegisterDataByTime($time - 3600 * 2, $time - 3600);
        $this->assertEmpty($result);
    }

    public function testFindUnLockedUsersByUserIds()
    {
        $this->getUserDao()->create([
            'id' => 100,
            'nickname' => '1@edusoho.com',
            'password' => '123456',
            'salt' => base_convert(sha1(uniqid(mt_rand(), true)), 16, 36),
            'email' => '1@edusoho.com',
            'type' => 'default',
            'roles' => ['ROLE_USER'],
            'uuid' => 1,
            'locked' => 1,
        ]);

        $this->getUserDao()->create([
            'id' => 101,
            'nickname' => '2@edusoho.com',
            'password' => '123456',
            'salt' => base_convert(sha1(uniqid(mt_rand(), true)), 16, 36),
            'email' => '2@edusoho.com',
            'type' => 'default',
            'roles' => ['ROLE_USER'],
            'uuid' => 2,
            'locked' => 0,
        ]);

        $this->getUserDao()->create([
            'id' => 102,
            'nickname' => '3@edusoho.com',
            'password' => '123456',
            'salt' => base_convert(sha1(uniqid(mt_rand(), true)), 16, 36),
            'email' => '3@edusoho.com',
            'type' => 'default',
            'roles' => ['ROLE_USER'],
            'uuid' => 3,
            'locked' => 0,
        ]);

        $users = $this->getUserService()->findUnLockedUsersByUserIds([100, 101, 102]);
        $this->assertEquals(count($users), 2);
    }

    protected function getDefaultMockFields()
    {
        return [
            'id' => '3',
            'nickname' => 'test',
            'roles' => ['ROLE_ADMIN'],
            'password' => '3DMYb8GyEXk32ruFzw4lxy2elz6/aoPtA5X8vCTWezg=',
            'salt' => 'qunt972ow5c48k4wc8k0ss448os0oko',
            'email' => '80@qq.com',
            'type' => 'system',
            'inviteCode' => 'test-code',
            'uuid' => '439c25e94b7833262d7aa4f1f19b4f93a89e6aed',
        ];
    }

    private function getUserDao()
    {
        return $this->createDao('User:UserDao');
    }

    private function getUserService()
    {
        return $this->createService('User:UserService');
    }

    private function getUserProfileDao()
    {
        return $this->createDao('User:UserProfileDao');
    }
}
