<?php

namespace ApiBundle\Api\Resource\LiveStatistic;

use ApiBundle\Api\ApiRequest;
use ApiBundle\Api\Resource\AbstractResource;
use AppBundle\Common\ArrayToolkit;
use Biz\Activity\LiveActivityException;
use Biz\Activity\Service\ActivityService;
use Biz\InfoSecurity\Service\MobileMaskService;
use Biz\Live\LiveStatisticsException;
use Biz\Live\Service\LiveStatisticsService;
use Biz\Task\Service\TaskService;
use Biz\Task\TaskException;
use Biz\User\Service\UserService;

class LiveStatisticRollCall extends AbstractResource
{
    public function search(ApiRequest $request, $taskId)
    {
        $task = $this->getTaskService()->getTask($taskId);
        if (empty($task)) {
            TaskException::NOTFOUND_TASK();
        }
        $activity = $this->getActivityService()->getActivity($task['activityId'], true);
        if (empty($activity['ext']['liveId'])) {
            LiveActivityException::NOTFOUND_LIVE();
        }
        $status = $request->query->get('status');
        $statistics = $this->getLiveStatisticsService()->getCheckinStatisticsByLiveId($activity['ext']['liveId']);
        if ($status && !empty($statistics['data']['detail'])) {
            $groupedStatistics = ArrayToolkit::group($statistics['data']['detail'], 'checkin');
            $groupedStatistics = [
                empty($groupedStatistics[0]) ? [] : $groupedStatistics[0],
                empty($groupedStatistics[1]) ? [] : $groupedStatistics[1],
            ];
            $statistics['data']['detail'] = 'checked' == $status ? $groupedStatistics[1] : $groupedStatistics[0];
        }

        $statistics = empty($statistics['data']['detail']) ? [] : $statistics['data']['detail'];
        list($offset, $limit) = $this->getOffsetAndLimit($request);
        $data = array_slice($statistics, $offset, $limit);

        return $this->makePagingObject($this->processStatisticData($data), count($statistics), $offset, $limit);
    }

    protected function processStatisticData($statistics)
    {
        $userIds = ArrayToolkit::column($statistics, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);
        foreach ($statistics as &$statistic) {
            $statistic['nickname'] = empty($users[$statistic['userId']]) ? '--' : $users[$statistic['userId']]['nickname'];
            $statistic['email'] = empty($users[$statistic['userId']]) || empty($users[$statistic['userId']]['emailVerified']) ? '--' : $users[$statistic['userId']]['email'];
            $statistic['checkin'] = empty($statistic['checkin']) ? 0 : 1;
            $statistic['mobile'] = empty($users[$statistic['userId']]) || empty($users[$statistic['userId']]['verifiedMobile']) ? '' : $users[$statistic['userId']]['verifiedMobile'];
            $statistic['encryptedMobile'] = empty($statistic['mobile']) ? '' : $this->getMobileMaskService()->encryptMobile($statistic['mobile']);
            $statistic['mobile'] = empty($statistic['mobile']) ? '--' : $this->getMobileMaskService()->maskMobile($statistic['mobile']);
        }

        return $statistics;
    }

    public function processJsonData($liveId)
    {
        try {
            $checkin = $this->getLiveStatisticsService()->updateCheckinStatistics($liveId);
            $visitor = $this->getLiveStatisticsService()->updateVisitorStatistics($liveId);
        } catch (LiveStatisticsException $e) {
        }
    }

    /**
     * @return LiveStatisticsService
     */
    protected function getLiveStatisticsService()
    {
        return $this->service('Live:LiveStatisticsService');
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
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->service('User:UserService');
    }

    /**
     * @return MobileMaskService
     */
    protected function getMobileMaskService()
    {
        return $this->service('InfoSecurity:MobileMaskService');
    }
}
