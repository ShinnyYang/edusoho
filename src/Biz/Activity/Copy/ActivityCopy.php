<?php

namespace Biz\Activity\Copy;

use Biz\AbstractCopy;
use Biz\Activity\Config\Activity;
use Biz\Activity\Dao\ActivityDao;

class ActivityCopy extends AbstractCopy
{
    public function preCopy($source, $options)
    {
        // TODO: Implement preCopy() method.
    }

    public function doCopy($source, $options)
    {
        $course = $options['originCourse'];
        $newCourse = $options['newCourse'];
        $newCourseSet = $options['newCourseSet'];
        $cycleDifference = 0;
        $activities = $this->getActivityDao()->findByCourseId($course['id']);
        if (empty($activities)) {
            return [];
        }
        $activityMap = [];
        foreach ($activities as $activity) {
            $newActivity = $this->partsFields($activity);

            $newActivity['fromUserId'] = $this->biz['user']['id'];
            $newActivity['fromCourseId'] = $newCourse['id'];
            $newActivity['fromCourseSetId'] = $newCourseSet['id'];
            $newActivity['copyId'] = $activity['id'];

            if ('live' == $newActivity['mediaType']) { //直播
                if ($activity['endTime'] < time()) {
                    $newActivity['startTime'] = $activity['startTime'];
                    $newActivity['endTime'] = $activity['endTime'];
                } else {
                    $newActivity['startTime'] = time();
                    $newActivity['endTime'] = $newActivity['startTime'] + $newActivity['length'] * 60;
                }

                if (!empty($options['newMultiClass'])) {
                    if (0 == $cycleDifference) {
                        $cycleDifference = abs(round((time() - $activity['startTime']) / 86400));
                    }
                    $startTime = $activity['startTime'] + $cycleDifference * 86400;
                    $newActivity['startTime'] = $startTime < time() ? $startTime + 86400 : $startTime; //当前时间无法创建 延迟 1天
                    $newActivity['endTime'] = $newActivity['startTime'] + $newActivity['length'] * 60;
                }
            }

            $ext = $this->getActivityConfig($activity['mediaType'])->copy($activity, [
                'refLiveroom' => $activity['endTime'] < time(),
                'newActivity' => $newActivity,
                'isCopy' => true,
                'newMultiClass' => !empty($options['newMultiClass']) ? $options['newMultiClass'] : [],
            ]);

            if (!empty($ext)) {
                $newActivity['mediaId'] = $ext['id'];
            }

            $newActivity = $this->getActivityDao()->create($newActivity);
            $options['newActivity'] = $newActivity;
            $options['originActivity'] = $activity;
            $this->doChildrenProcess($source, $options);
            $activityMap[$activity['id']] = $newActivity['id'];
        }
    }

    protected function doChildrenProcess($source, $options)
    {
        $childrenNodes = $this->getChildrenNodes();
        foreach ($childrenNodes as $childrenNode) {
            $CopyClass = $childrenNode['class'];
            $copyClass = new $CopyClass($this->biz, $childrenNode, isset($childrenNode['auto']) ? $childrenNode['auto'] : true);
            $copyClass->copy($source, $options);
        }
    }

    protected function getFields()
    {
        return [
            'mediaType',
            'title',
            'remark',
            'content',
            'length',
            'startTime',
            'endTime',
            'finishType',
            'finishData',
        ];
    }

    /**
     * @return ActivityDao
     */
    private function getActivityDao()
    {
        return $this->biz->dao('Activity:ActivityDao');
    }

    /**
     * @param  $type
     *
     * @return Activity
     */
    private function getActivityConfig($type)
    {
        return $this->biz["activity_type.{$type}"];
    }
}
