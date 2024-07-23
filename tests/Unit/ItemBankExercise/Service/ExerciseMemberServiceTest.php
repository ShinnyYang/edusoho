<?php

namespace Tests\Unit\ItemBankExercise\Service;

use Biz\BaseTestCase;
use Biz\ItemBankExercise\Dao\ExerciseMemberDao;
use Biz\ItemBankExercise\OperateReason;
use Biz\ItemBankExercise\Service\ExerciseMemberService;
use Biz\ItemBankExercise\Service\ExerciseService;
use Biz\Role\Util\PermissionBuilder;
use Biz\User\CurrentUser;
use Biz\User\Service\UserService;

class ExerciseMemberServiceTest extends BaseTestCase
{
    public function testUpdateMasteryRate()
    {
        $this->createExercise();
        $this->mockBiz(
            'QuestionBank:QuestionBankService',
            [
                [
                    'functionName' => 'getQuestionBank',
                    'returnValue' => [
                        'itemBank' => [
                            'id' => 1,
                            'question_num' => 10,
                        ],
                    ],
                ],
            ]
        );
        $this->createExerciseMember();
        $this->mockBiz(
            'ItemBankExercise:ExerciseQuestionRecordService',
            [
                [
                    'functionName' => 'findByUserIdAndExerciseId',
                    'returnValue' => [
                        ['status' => 'right'],
                        ['status' => 'wrong'],
                    ],
                ],
            ]
        );

        $member = $this->getExerciseMemberService()->updateMasteryRate(1, 1);

        $this->assertEquals(2, $member['doneQuestionNum']);
        $this->assertEquals(1, $member['rightQuestionNum']);
        $this->assertEquals(10.0, $member['masteryRate']);
        $this->assertEquals(20.0, $member['completionRate']);
    }

    public function testCount()
    {
        $this->batchCreateExerciseMembers();

        $res = $this->getExerciseMemberService()->count([
            'exerciseId' => 1,
            'role' => 'student',
        ]);

        $this->assertEquals(1, $res);
    }

    public function testUpdate()
    {
        $member = $this->createExerciseMember();
        $res = $this->getExerciseMemberService()->update(
            $member['id'],
            [
                'doneQuestionNum' => 1,
                'rightQuestionNum' => 1,
                'completionRate' => 0.1,
                'masteryRate' => 0.1,
            ]
        );
        $this->assertEquals(1, $res['doneQuestionNum']);
        $this->assertEquals(1, $res['rightQuestionNum']);
        $this->assertEquals(0.1, $res['completionRate']);
        $this->assertEquals(0.1, $res['masteryRate']);
    }

    public function testGetByExerciseIdAndUserId()
    {
        $member = $this->createExerciseMember();
        $res = $this->getExerciseMemberService()->getByExerciseIdAndUserId(1, 1);
        $this->assertEquals($member['exerciseId'], $res['exerciseId']);
        $this->assertEquals($member['userId'], $res['userId']);
    }

    public function testSearch()
    {
        $this->batchCreateExerciseMembers();
        $res = $this->getExerciseMemberService()->search(
            [
                'exerciseId' => 1,
                'role' => 'student',
            ],
            null,
            0,
            1
        );
        $this->assertEquals(1, count($res));
        $this->assertEquals(1, $res[0]['exerciseId']);
        $this->assertEquals('student', $res[0]['role']);
    }

    public function testIsExerciseMember()
    {
        $user = $this->createNormalUser();
        $exercise = $this->createExercise();
        $this->getExerciseMemberDao()->create(
            [
                'exerciseId' => $exercise['id'],
                'questionBankId' => 1,
                'userId' => $user['id'],
                'role' => 'student',
                'remark' => 'aaa',
            ]
        );
        $result = $this->getExerciseMemberService()->isExerciseMember($exercise['id'], $user['id']);
        $this->assertEquals(true, $result);
    }

    public function testBecomeStudent()
    {
        $user = $this->createNormalUser();
        $exercise = $this->getExerciseService()->create(
            [
                'id' => 1,
                'title' => 'test',
                'questionBankId' => 1,
                'categoryId' => 1,
                'seq' => 1,
                'status' => 'published',
                'expiryMode' => 'forever',
            ]
        );

        $result = $this->getExerciseMemberService()->isExerciseMember($exercise['id'], $user['id']);
        $this->assertEquals(false, $result);

        $this->getExerciseMemberService()->becomeStudent($exercise['id'], $user['id'], ['remark' => '123', 'reason' => OperateReason::JOIN_BY_IMPORT, 'reasonType' => OperateReason::JOIN_BY_IMPORT_TYPE, 'source' => 'outside']);
        $result = $this->getExerciseMemberService()->isExerciseMember($exercise['id'], $user['id']);
        $this->assertEquals(true, $result);
    }

    public function testAddTeacher()
    {
        $exercise = $this->createExercise();
        $res = $this->getExerciseService()->isExerciseTeacher($exercise['id'], 2);
        $this->assertEquals(false, $res);
        $currentUser = new CurrentUser();
        $currentUser->fromArray([
            'id' => 2,
            'nickname' => 'admin3',
            'email' => 'admin3@admin.com',
            'password' => 'admin',
            'currentIp' => '127.0.0.1',
            'roles' => ['ROLE_USER', 'ROLE_SUPER_ADMIN'],
        ]);
        $currentUser->setPermissions(PermissionBuilder::instance()->getPermissionsByRoles($currentUser->getRoles()));
        $this->getServiceKernel()->setCurrentUser($currentUser);
        $this->getExerciseMemberService()->addTeacher($exercise['id']);
        $res = $this->getExerciseService()->isExerciseTeacher($exercise['id'], 2);
        $this->assertEquals(true, $res);
    }

    public function testLockStudent()
    {
        $exercise = $this->createExercise();
        $result = $this->getExerciseMemberService()->lockStudent($exercise['id'], 100);
        $this->assertNull($result);

        $member = $this->createExerciseMember(['exerciseId' => $exercise['id'], 'userId' => 10]);
        $result = $this->getExerciseMemberService()->lockStudent($member['exerciseId'], $member['userId']);

        $this->assertEquals(1, $result['locked']);
    }

    public function testUnlockStudent()
    {
        $exercise = $this->createExercise();
        $result = $this->getExerciseMemberService()->unlockStudent($exercise['id'], 100);
        $this->assertNull($result);

        $member = $this->createExerciseMember(['exerciseId' => $exercise['id'], 'userId' => 10]);
        $result = $this->getExerciseMemberService()->lockStudent($member['exerciseId'], $member['userId']);

        $this->assertEquals(1, $result['locked']);

        $result = $this->getExerciseMemberService()->unlockStudent($member['exerciseId'], $member['userId']);
        $this->assertEquals(0, $result['locked']);
    }

    public function testRemoveStudent()
    {
        $exercise = $this->createExercise();
        $member = $this->createExerciseMember(['exerciseId' => $exercise['id'], 'userId' => 10]);
        $this->getExerciseMemberService()->removeStudent($member['exerciseId'], $member['userId']);
        $result = $this->getExerciseMemberService()->getByExerciseIdAndUserId($member['exerciseId'], $member['userId']);

        $this->assertEmpty($result);
    }

    public function testRemoveStudents()
    {
        $exercise = $this->createExercise();
        $member = $this->createExerciseMember(['exerciseId' => $exercise['id'], 'userId' => 10]);
        $this->getExerciseMemberService()->removeStudents($member['exerciseId'], [$member['userId']]);
        $result = $this->getExerciseMemberService()->getByExerciseIdAndUserId($member['exerciseId'], $member['userId']);

        $this->assertEmpty($result);
    }

    public function testGetExerciseMember()
    {
        $member = $this->createExerciseMember();
        $res = $this->getExerciseMemberService()->getExerciseMember($member['exerciseId'], $member['userId']);
        $this->assertEquals($member['exerciseId'], $res['exerciseId']);
        $this->assertEquals($member['userId'], $res['userId']);
    }

    public function testRemarkStudent()
    {
        $member = $this->createExerciseMember();
        $res = $this->getExerciseMemberService()->remarkStudent($member['exerciseId'], $member['userId'], 'remark');
        $this->assertEquals('remark', $res['remark']);
    }

    public function testBatchUpdateMemberDeadlines()
    {
        $user = $this->createNormalUser();
        $exercise = $this->createExercise();
        $member = $this->getExerciseMemberDao()->create(
            [
                'exerciseId' => $exercise['id'],
                'questionBankId' => 1,
                'userId' => $user['id'],
                'role' => 'student',
                'remark' => 'aaa',
            ]
        );
        $deadline = time();
        $this->getExerciseMemberService()->batchUpdateMemberDeadlines($exercise['id'], [$user['id']], ['updateType' => 'deadline', 'deadline' => $deadline]);
        $result = $this->getExerciseMemberService()->getExerciseMember($exercise['id'], $user['id']);
        $this->assertEquals($deadline, (int) $result['deadline']);
    }

    public function testCheckUpdateDeadline()
    {
        $exercise = $this->createExercise();
        $this->batchCreateExerciseMembers();
        $res = $this->getExerciseMemberService()->checkUpdateDeadline($exercise['id'], [1, 2], ['deadline' => strtotime(date('Y-m-d'))]);
        $this->assertFalse($res);
    }

    public function testIsMemberNonExpired()
    {
        $exercise = $this->createExercise();
        $member = $this->createExerciseMember();
        $res = $this->getExerciseMemberService()->isMemberNonExpired($exercise, $member);
        $this->assertTrue($res);
    }

    public function testQuitExerciseByDeadlineReach()
    {
        $exercise = $this->createExercise();
        $member = $this->getExerciseMemberDao()->create(
            [
                'exerciseId' => 1,
                'questionBankId' => 1,
                'userId' => 2,
                'role' => 'student',
                'remark' => 'aaa',
                'deadline' => strtotime('-1day'),
            ]
        );
        $this->getExerciseService()->updateExerciseStatistics($exercise['id'], ['studentNum']);

        $this->getExerciseMemberService()->quitExerciseByDeadlineReach(2, $exercise['id']);
        $res = $this->getExerciseService()->get($exercise['id']);
        $result = $this->getExerciseMemberService()->getExerciseMember($exercise['id'], $member['userId']);

        $this->assertEquals(0, $res['studentNum']);
        $this->assertEmpty($result);
    }

    public function testFindByUserIdAndRole()
    {
        $this->batchCreateExerciseMembers();
        $res = $this->getExerciseMemberService()->findByUserIdAndRole(2, 'student');
        $this->assertEquals(1, count($res));
        $this->assertEquals('student', $res[0]['role']);
    }

    private function createExercise()
    {
        return $this->getExerciseService()->create(
            [
                'id' => 1,
                'title' => 'test',
                'questionBankId' => 1,
                'categoryId' => 1,
                'seq' => 1,
                'expiryMode' => 'forever',
            ]
        );
    }

    private function createNormalUser()
    {
        return $this->getUserService()->register([
            'id' => 1,
            'email' => 'normal@user.com',
            'nickname' => 'normal',
            'password' => 'user123',
            'currentIp' => '127.0.0.1',
            'roles' => ['ROLE_USER'],
        ]);
    }

    protected function createExerciseMember($member = [])
    {
        $default = [
            'exerciseId' => 1,
            'questionBankId' => 1,
            'userId' => 1,
            'role' => 'student',
            'remark' => 'aaa',
        ];
        $member = array_merge($default, $member);

        return $this->getExerciseMemberDao()->create($member);
    }

    protected function batchCreateExerciseMembers()
    {
        return $this->getExerciseMemberDao()->batchCreate(
            [
                ['exerciseId' => 1, 'questionBankId' => 1, 'userId' => 1, 'role' => 'teacher', 'remark' => 'aaa'],
                ['exerciseId' => 1, 'questionBankId' => 1, 'userId' => 2, 'role' => 'student', 'remark' => 'bbb'],
                ['exerciseId' => 2, 'questionBankId' => 2, 'userId' => 3, 'role' => 'student', 'remark' => 'ccc'],
            ]
        );
    }

    /**
     * @return ExerciseMemberDao
     */
    protected function getExerciseMemberDao()
    {
        return $this->createDao('ItemBankExercise:ExerciseMemberDao');
    }

    /**
     * @return ExerciseMemberService
     */
    protected function getExerciseMemberService()
    {
        return $this->createService('ItemBankExercise:ExerciseMemberService');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    /**
     * @return ExerciseService
     */
    protected function getExerciseService()
    {
        return $this->createService('ItemBankExercise:ExerciseService');
    }
}
