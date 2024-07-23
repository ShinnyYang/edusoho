<?php

namespace Biz\Activity\Type;

use AppBundle\Common\ArrayToolkit;
use Biz\Activity\ActivityException;
use Biz\Activity\Config\Activity;
use Biz\Activity\Dao\ActivityDao;
use Biz\Activity\Service\LiveActivityService;
use Biz\Common\CommonException;

class Live extends Activity
{
    protected function registerListeners()
    {
        return [
        ];
    }

    public function preCreateCheck($fields)
    {
        if (!ArrayToolkit::requireds($fields, ['fromCourseId', 'startTime', 'length'], true)) {
            throw CommonException::ERROR_PARAMETER_MISSING();
        }

        $overlapTimeActivities = $this->getActivityDao()->findOverlapTimeActivitiesByCourseId(
            $fields['fromCourseId'],
            $fields['startTime'],
            $fields['startTime'] + $fields['length'] * 60
        );

        if ($overlapTimeActivities) {
            throw ActivityException::LIVE_OVERLAP_TIME();
        }
    }

    public function preUpdateCheck($activity, $newFields)
    {
        if (empty($newFields['startTime']) || empty($newFields['length'])) {
            return;
        }

        if (!ArrayToolkit::requireds($newFields, ['fromCourseId', 'startTime', 'length'], true)) {
            throw CommonException::ERROR_PARAMETER_MISSING();
        }

        $overlapTimeActivities = $this->getActivityDao()->findOverlapTimeActivitiesByCourseId(
            $newFields['fromCourseId'],
            $newFields['startTime'],
            $newFields['startTime'] + $newFields['length'] * 60,
            $activity['id']
        );

        if ($overlapTimeActivities) {
            throw ActivityException::LIVE_OVERLAP_TIME();
        }
    }

    public function create($fields)
    {
        $files = empty($fields['materials']) ? [] : json_decode($fields['materials'], true);
        $fields['fileIds'] = empty($files) ? [] : ArrayToolkit::column($files, 'fileId');

        return $this->getLiveActivityService()->createLiveActivity($fields);
    }

    public function copy($activity, $config = [])
    {
        $user = $this->getCurrentUser();
        $live = $this->getLiveActivityService()->getLiveActivity($activity['mediaId']);
        if (empty($config['refLiveroom'])) {
            $activity['fromUserId'] = $user['id'];
            $activity['startTime'] = $config['newActivity']['startTime'];
            $activity['endTime'] = $config['newActivity']['endTime'];
            unset($activity['id']);
            if (!$config['newMultiClass']) {
                unset($activity['startTime']);
                unset($activity['endTime']);
            }

            return $this->getLiveActivityService()->createLiveActivity($activity, true);
        }

        return $live;
    }

    public function sync($sourceActivity, $activity)
    {
        //引用的是同一个直播教室，无需同步
        return null;
    }

    public function allowTaskAutoStart($activity)
    {
        return $activity['startTime'] <= time();
    }

    public function update($id, &$fields, $activity)
    {
        if (isset($fields['materials'])) {
            $files = json_decode($fields['materials'], true);
            $fields['fileIds'] = empty($files) ? [] : ArrayToolkit::column($files, 'fileId');
        }

        list($liveActivity, $fields) = $this->getLiveActivityService()->updateLiveActivity($id, $fields, $activity);

        return $liveActivity;
    }

    public function get($targetId)
    {
        return $this->getLiveActivityService()->getLiveActivity($targetId);
    }

    public function find($targetIds, $showCloud = 1)
    {
        return $this->getLiveActivityService()->findLiveActivitiesByIds($targetIds);
    }

    public function delete($targetId)
    {
        $live = $this->getLiveActivityService()->getLiveActivity($targetId);
        foreach ($live['fileIds'] as $fileId) {
            $this->getUploadFileService()->updateUsedCount($fileId);
        }

        $conditions = ['mediaType' => 'live', 'mediaId' => $targetId];
        $count = $this->getActivityService()->count($conditions);
        if (1 == $count) {
            return $this->getLiveActivityService()->deleteLiveActivity($targetId);
        }
    }

    public function allowEventAutoTrigger()
    {
        return false;
    }

    /**
     * @return LiveActivityService
     */
    protected function getLiveActivityService()
    {
        return $this->getBiz()->service('Activity:LiveActivityService');
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->getBiz()->service('Activity:ActivityService');
    }

    /**
     * @return ActivityDao
     */
    private function getActivityDao()
    {
        return $this->getBiz()->dao('Activity:ActivityDao');
    }

    /**
     * @return UploadFileService
     */
    protected function getUploadFileService()
    {
        return $this->getBiz()->service('File:UploadFileService');
    }
}
