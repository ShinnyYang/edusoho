<?php

namespace ApiBundle\Api\Resource\LiveStatistic;

use ApiBundle\Api\ApiRequest;
use ApiBundle\Api\Resource\AbstractResource;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\SimpleValidator;
use Biz\Activity\LiveActivityException;
use Biz\Activity\Service\ActivityService;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\MemberService;
use Biz\InfoSecurity\Service\MobileMaskService;
use Biz\LiveStatistics\Service\LiveCloudStatisticsService;
use Biz\Task\Service\TaskService;
use Biz\Task\TaskException;
use Biz\User\Service\UserService;

class LiveStatisticMember extends AbstractResource
{
    public function search(ApiRequest $request, $taskId)
    {
        $task = $this->getTaskService()->getTask($taskId);
        if (empty($task)) {
            TaskException::NOTFOUND_TASK();
        }
        $this->getCourseService()->tryManageCourse($task['courseId']);
        $activity = $this->getActivityService()->getActivity($task['activityId'], true);
        if (empty($activity['ext']['liveId'])) {
            LiveActivityException::NOTFOUND_LIVE();
        }
        $this->getLiveStatisticsService()->syncLiveMemberData($task['activityId']);

        list($offset, $limit) = $this->getOffsetAndLimit($request);
        $conditions = ['courseId' => $task['courseId'], 'liveId' => $activity['ext']['liveId'], 'excludeUserIds' => [$activity['ext']['anchorId']]];
        $this->buildUserConditions($request, $conditions);
        $members = $this->getLiveStatisticsService()->searchCourseMemberLiveData($conditions, $offset, $limit);
        unset($conditions['liveId']);

        return $this->makePagingObject($this->processMemberData($activity, $members), $this->getCourseMemberService()->countMembers($conditions), $offset, $limit);
    }

    protected function buildUserConditions(ApiRequest $request, &$conditions)
    {
        $nameOrMobile = $request->query->get('nameOrMobile', '');
        if (empty($nameOrMobile)) {
            return;
        }
        if (SimpleValidator::mobile($nameOrMobile)) {
            $user = $this->getUserService()->getUserByVerifiedMobile($nameOrMobile);
            $users = empty($user) ? [] : [$user];
        } else {
            $users = $this->getUserService()->searchUsers(
                ['nickname' => $nameOrMobile],
                [],
                0,
                PHP_INT_MAX,
                ['id']
            );
        }
        $conditions['userIds'] = array_column($users, 'id') ?: [-1];
    }

    protected function processMemberData($activity, $members)
    {
        $cloudStatisticData = $activity['ext']['cloudStatisticData'];
        $userIds = ArrayToolkit::column($members, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);
        $userProfiles = $this->getUserService()->findUserProfilesByIds($userIds);
        foreach ($members as &$member) {
            $member['truename'] = empty($userProfiles[$member['userId']]) ? '--' : $userProfiles[$member['userId']]['truename'];
            $member['nickname'] = empty($users[$member['userId']]) ? '--' : $users[$member['userId']]['nickname'];
            $member['email'] = empty($users[$member['userId']]) || empty($users[$member['userId']]['emailVerified']) ? '--' : $users[$member['userId']]['email'];
            $member['checkinNum'] = empty($cloudStatisticData['checkinNum']) || empty($member['checkinNum']) ? '--' : $member['checkinNum'].'/'.$cloudStatisticData['checkinNum'];
            $member['mobile'] = empty($users[$member['userId']]) || empty($users[$member['userId']]['verifiedMobile']) ? '' : $users[$member['userId']]['verifiedMobile'];
            $member['watchDuration'] = empty($member['watchDuration']) ? 0 : round($member['watchDuration'] / 60, 1);
            $member['answerNum'] = empty($member['answerNum']) ? 0 : $member['answerNum'];
            $member['chatNumber'] = empty($member['chatNum']) ? 0 : $member['chatNum'];
            $member['firstEnterTime'] = empty($member['firstEnterTime']) ? '--' : date('Y-m-d H:i', $member['firstEnterTime']);
            $member['encryptedMobile'] = empty($member['mobile']) ? '' : $this->getMobileMaskService()->encryptMobile($member['mobile']);
            $member['mobile'] = empty($member['mobile']) ? '--' : $this->getMobileMaskService()->maskMobile($member['mobile']);
        }

        return $members;
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->service('User:UserService');
    }

    /**
     * @return LiveCloudStatisticsService
     */
    protected function getLiveStatisticsService()
    {
        return $this->service('LiveStatistics:LiveCloudStatisticsService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->service('Course:CourseService');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->service('Task:TaskService');
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->service('Activity:ActivityService');
    }

    /**
     * @return MemberService
     */
    protected function getCourseMemberService()
    {
        return $this->service('Course:MemberService');
    }

    /**
     * @return MobileMaskService
     */
    protected function getMobileMaskService()
    {
        return $this->service('InfoSecurity:MobileMaskService');
    }
}
