<?php

namespace ApiBundle\Api\Resource\Activity;

use ApiBundle\Api\Resource\Filter;

class ActivityFilter extends Filter
{
    protected $publicFields = [
        'id', 'remark', 'ext', 'mediaType', 'mediaId', 'startTime', 'content', 'title', 'finishData', 'finishType', 'finishCondition', 'exam_mode'
    ];

    protected function publicFields(&$data)
    {
        if (!empty($data['ext']) && !empty($data['ext']['replayStatus'])) {
            $data['replayStatus'] = $data['ext']['replayStatus'];
        }

        if (!empty($data['ext']) && !empty($data['ext']['liveProvider'])) {
            $data['liveProvider'] = $data['ext']['liveProvider'];
        }

        if (!empty($data['finishData'])) {
            $data['finishDetail'] = $data['finishData'];
        }

        if (!empty($data['ext']) && !empty($data['ext']['file'])) {
            $data['mediaStorage'] = $data['ext']['file']['storage'];
        }

        if (!empty($data['ext']) && !empty($data['ext']['finishCondition'])) {
            $data['finishDetail'] = (string) $data['ext']['finishCondition']['finishScore'];
        }

        if (!empty($data['content'])) {
            $data['content'] = $this->convertAbsoluteUrl($data['content']);
        }

        //testpaper module
        if ('testpaper' == $data['mediaType']) {
            if (!empty($data['ext'])) {
                $data['testpaperInfo']['testpaperId'] = $data['ext']['mediaId'];
                $data['testpaperInfo']['testMode'] = $data['ext']['testMode'];
                $data['testpaperInfo']['limitTime'] = $data['ext']['limitedTime'];
                $data['testpaperInfo']['redoInterval'] = $data['ext']['redoInterval']; //分钟
                $data['testpaperInfo']['doTimes'] = $data['ext']['doTimes'];
                $data['testpaperInfo']['startTime'] = !empty($data['startTime']) ? $data['startTime'] : null;
                $data['testpaperInfo']['examMode'] = !empty($data['ext']['answerScene']['exam_mode']) ? $data['ext']['answerScene']['exam_mode'] : "0";
                /**
                 * @see \ApiBundle\Api\Resource\Course\CourseItemWithLesson::search()
                 */
                $data['testpaperInfo']['answerRecordId'] = !empty($data['ext']['answerRecordId']) ? $data['ext']['answerRecordId'] : "0";
            }
        }
        if ('download' == $data['mediaType']) {
            $data['content'] = null;
        }

        unset($data['ext']);
    }
}
