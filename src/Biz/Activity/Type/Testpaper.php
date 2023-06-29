<?php

namespace Biz\Activity\Type;

use AppBundle\Common\ArrayToolkit;
use Biz\Activity\ActivityException;
use Biz\Activity\Config\Activity;
use Biz\Activity\Service\ActivityService;
use Biz\Activity\Service\TestpaperActivityService;
use Biz\Testpaper\Service\TestpaperService;
use Biz\Testpaper\TestpaperException;
use Codeages\Biz\ItemBank\Answer\Service\AnswerRecordService;
use Codeages\Biz\ItemBank\Answer\Service\AnswerReportService;
use Codeages\Biz\ItemBank\Answer\Service\AnswerSceneService;
use Codeages\Biz\ItemBank\Answer\Service\AnswerService;
use Codeages\Biz\ItemBank\Assessment\Service\AssessmentService;

class Testpaper extends Activity
{
    // 考试及格后显示答案
    const ANSWER_MODE_PASSED = 1;

    const EXAM_MODE_SIMULATION = 0;

    const EXAM_MODE_PRACTICE = 1;

    protected function registerListeners()
    {
        return [
            'activity.created' => 'Biz\Activity\Listener\TestpaperActivityCreateListener',
        ];
    }

    public function get($targetId)
    {
        $activity = $this->getTestpaperActivityService()->getActivity($targetId);
        if ($activity) {
            $testpaper = $this->getAssessmentService()->getAssessment($activity['mediaId']);
            $activity['testpaper'] = $testpaper;
            $activity['answerScene'] = $this->getAnswerSceneService()->get($activity['answerSceneId']);
            $activity = $this->filterActivity($activity, $activity['answerScene']);
        }

        return $activity;
    }

    public function find($ids, $showCloud = 1)
    {
        return $this->getTestpaperActivityService()->findActivitiesByIds($ids);
    }

    public function create($fields)
    {
        $fields = $this->checkFields($fields);
        $fields = $this->filterFields($fields);

        try {
            $this->getBiz()['db']->beginTransaction();

            $answerScene = $this->getAnswerSceneService()->create([
                'name' => $fields['title'],
                'limited_time' => $fields['limitedTime'],
                'do_times' => $fields['doTimes'],
                'redo_interval' => $fields['redoInterval'],
                'need_score' => 1,
                'start_time' => $fields['startTime'],
                'pass_score' => empty($fields['passScore']) ? 0 : $fields['passScore'],
                'enable_facein' => empty($fields['enable_facein']) ? 0 : $fields['enable_facein'],
                'exam_mode' => empty($fields['exam_mode']) ? self::EXAM_MODE_SIMULATION : $fields['exam_mode'],
                'end_time' => empty($fields['endTime']) ? 0 : $fields['endTime'],
                'is_items_seq_random' => empty($fields['isItemsSeqRandom']) ? 0 : $fields['isItemsSeqRandom'],
                'is_options_seq_random' => empty($fields['isOptionsSeqRandom']) ? 0 : $fields['isOptionsSeqRandom'],
            ]);

            $testpaperActivity = $this->getTestpaperActivityService()->createActivity([
                'mediaId' => $fields['mediaId'],
                'checkType' => empty($fields['checkType']) ? '' : $fields['checkType'],
                'requireCredit' => empty($fields['requireCredit']) ? 0 : $fields['requireCredit'],
                'answerSceneId' => $answerScene['id'],
                'finishCondition' => $fields['finishCondition'],
                'answerMode' => $fields['answerMode'],
                'customComments' => $fields['customComments'],
            ]);

            $this->getBiz()['db']->commit();

            return $testpaperActivity;
        } catch (\Exception $e) {
            $this->getBiz()['db']->rollback();
            throw $e;
        }
    }

    public function copy($activity, $config = [])
    {
        if ('testpaper' !== $activity['mediaType']) {
            return null;
        }

        $testpaperActivity = $this->get($activity['mediaId']);

        $newExt = [
            'title' => $activity['title'],
            'startTime' => $activity['startTime'],
            'finishData' => $activity['finishData'],
            'testpaperId' => $testpaperActivity['mediaId'],
            'doTimes' => $testpaperActivity['answerScene']['do_times'],
            'redoInterval' => $testpaperActivity['answerScene']['redo_interval'],
            'limitedTime' => $testpaperActivity['answerScene']['limited_time'],
            'enable_facein' => $testpaperActivity['answerScene']['enable_facein'],
            'exam_mode' => $testpaperActivity['answerScene']['exam_mode'],
            'checkType' => $testpaperActivity['checkType'],
            'requireCredit' => $testpaperActivity['requireCredit'],
            'testMode' => $testpaperActivity['testMode'],
            'finishCondition' => $testpaperActivity['finishCondition'],
            'answerMode' => $testpaperActivity['answerMode'],
        ];

        return $this->create($newExt);
    }

    public function sync($sourceActivity, $activity)
    {
        $sourceExt = $this->get($sourceActivity['mediaId']);
        $ext = $this->get($activity['mediaId']);

        $ext['startTime'] = $sourceActivity['startTime'];
        $ext['title'] = $sourceActivity['title'];
        $ext['finishData'] = $sourceActivity['finishData'];
        $ext['testpaperId'] = $sourceExt['mediaId'];
        $ext['doTimes'] = $sourceExt['answerScene']['do_times'];
        $ext['redoInterval'] = $sourceExt['answerScene']['redo_interval'];
        $ext['limitedTime'] = $sourceExt['answerScene']['limited_time'];
        $ext['enable_facein'] = $sourceExt['answerScene']['enable_facein'];
        $ext['exam_mode'] = $sourceExt['answerScene']['exam_mode'];
        $ext['checkType'] = $sourceExt['checkType'];
        $ext['requireCredit'] = $sourceExt['requireCredit'];
        $ext['testMode'] = $sourceExt['testMode'];
        $ext['finishCondition'] = $sourceExt['finishCondition'];
        $ext['answerMode'] = $sourceExt['answerMode'];

        return $this->update($ext['id'], $ext, $activity);
    }

    public function update($targetId, &$fields, $activity)
    {
        $activity = $this->get($targetId);

        if (!$activity) {
            throw ActivityException::NOTFOUND_ACTIVITY();
        }

        $filterFields = $this->filterFields($fields);

        try {
            $this->getBiz()['db']->beginTransaction();

            $answerScene = $this->getAnswerSceneService()->update($activity['answerScene']['id'], [
                'name' => $filterFields['title'],
                'limited_time' => $filterFields['limitedTime'],
                'do_times' => $filterFields['doTimes'],
                'redo_interval' => $filterFields['redoInterval'],
                'start_time' => $filterFields['startTime'],
                'pass_score' => empty($filterFields['passScore']) ? 0 : $filterFields['passScore'],
                'enable_facein' => empty($filterFields['enable_facein']) ? 0 : $filterFields['enable_facein'],
                'exam_mode' => empty($filterFields['exam_mode']) ? self::EXAM_MODE_SIMULATION : $filterFields['exam_mode'],
            ]);

            $testpaperActivity = $this->getTestpaperActivityService()->updateActivity($activity['id'], [
                'mediaId' => $filterFields['mediaId'],
                'checkType' => empty($filterFields['checkType']) ? '' : $filterFields['checkType'],
                'requireCredit' => empty($filterFields['requireCredit']) ? 0 : $filterFields['requireCredit'],
                'finishCondition' => $filterFields['finishCondition'],
                'answerMode' => $filterFields['answerMode'],
                'customComments' => $filterFields['customComments'],
            ]);

            $this->getBiz()['db']->commit();

            return $testpaperActivity;
        } catch (\Exception $e) {
            $this->getBiz()['db']->rollback();
            throw $e;
        }
    }

    public function delete($targetId)
    {
        return $this->getTestpaperActivityService()->deleteActivity($targetId);
    }

    public function isFinished($activityId)
    {
        $user = $this->getCurrentUser();

        $activity = $this->getActivityService()->getActivity($activityId, true);
        $testpaperActivity = $activity['ext'];

        $answerRecord = $this->getAnswerRecordService()->getLatestAnswerRecordByAnswerSceneIdAndUserId(
            $testpaperActivity['answerScene']['id'],
            $user['id']
        );

        if (empty($answerRecord)) {
            return false;
        }
        if (!in_array(
            $answerRecord['status'],
            [AnswerService::ANSWER_RECORD_STATUS_REVIEWING, AnswerService::ANSWER_RECORD_STATUS_FINISHED]
        )) {
            return false;
        }

        if ('submit' === $activity['finishType']) {
            return true;
        }

        $answerReport = $this->getAnswerReportService()->getSimple($answerRecord['answer_report_id']);
        if (AnswerService::ANSWER_RECORD_STATUS_FINISHED == $answerRecord['status'] && 'score' === $activity['finishType'] && $answerReport['score'] >= $testpaperActivity['finishCondition']['finishScore']) {
            return true;
        }

        return false;
    }

    protected function checkFields($fields)
    {
        if (!empty($fields['isLimitDoTimes']) && !empty($fields['doTimes']) && $fields['doTimes'] > 100) {
            throw TestpaperException::TESTPAPER_DOTIMES_LIMIT();
        }

        if (!empty($fields['startTime']) && $fields['startTime'] < time()) {
            throw TestpaperException::START_TIME_EARLIER();
        }

        if (!empty($fields['endTime']) && $fields['endTime'] <= $fields['startTime']) {
            throw TestpaperException::END_TIME_EARLIER();
        }

        return $fields;
    }

    protected function filterFields($fields)
    {
        $testPaper = $this->getAssessmentService()->getAssessment($fields['testpaperId']);
        $fields['passScore'] = empty($fields['finishData']) ? 0 : round(
            $testPaper['total_score'] * $fields['finishData'],
            0
        );

        if (!empty($fields['finishType'])) {
            if ('score' == $fields['finishType']) {
                $fields['finishCondition'] = [
                    'type' => 'score',
                    'finishScore' => $fields['passScore'],
                ];
            } else {
                $fields['finishCondition'] = [];
            }
        }

        $fields['customComments'] = [];
        if (!empty($fields['start'])) {
            foreach ($fields['start'] as $key => $val) {
                $fields['customComments'][] = [
                    'start' => $val,
                    'end' => $fields['end'][$key],
                    'comment' => $fields['comment'][$key],
                ];
            }
        }

        $filterFields = ArrayToolkit::parts(
            $fields,
            [
                'title',
                'testpaperId',
                'doTimes',
                'redoInterval',
                'length',
                'limitedTime',
                'checkType',
                'requireCredit',
                'testMode',
                'finishCondition',
                'startTime',
                'passScore',
                'enable_facein',
                'answerMode',
                'customComments',
                'exam_mode',
                'endTime',
                'isItemsSeqRandom',
                'isOptionsSeqRandom',
            ]
        );

        if (isset($filterFields['length'])) {
            $filterFields['limitedTime'] = $filterFields['length'];
            unset($filterFields['length']);
        }

        if (isset($filterFields['doTimes']) && 0 == $filterFields['doTimes']) {
            $filterFields['testMode'] = 'normal';
            $filterFields['answerMode'] = isset($fields['answerMode']) ? $fields['answerMode'] : 0;
        } else {
            $filterFields['answerMode'] = 0;
        }

        /* #73707
        *不限考试次数时,即:无考试开始时间限制,且startTime默认置0.
        *单次考试次数时,且不限考试开始时间,即:startTime补充置0.
        */
        if ((isset($filterFields['doTimes']) && 0 == $filterFields['doTimes']) || (isset($filterFields['testMode']) && 'normal' == $filterFields['testMode'])) {
            $filterFields['startTime'] = 0;
        }

        $filterFields['mediaId'] = $filterFields['testpaperId'];
        unset($filterFields['testpaperId']);

        return $filterFields;
    }

    protected function filterActivity($activity, $scene)
    {
        if (!empty($scene)) {
            $activity['doTimes'] = $scene['do_times'];
            $activity['redoInterval'] = $scene['redo_interval'];
            $activity['limitedTime'] = $scene['limited_time'];
            $activity['testMode'] = !empty($scene['start_time']) ? 'realTime' : 'normal';
        }

        return $activity;
    }

    /**
     * @return TestpaperActivityService
     */
    protected function getTestpaperActivityService()
    {
        return $this->getBiz()->service('Activity:TestpaperActivityService');
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->getBiz()->service('Activity:ActivityService');
    }

    /**
     * @return TestpaperService
     */
    protected function getTestpaperService()
    {
        return $this->getBiz()->service('Testpaper:TestpaperService');
    }

    /**
     * @return AssessmentService
     */
    protected function getAssessmentService()
    {
        return $this->getBiz()->service('ItemBank:Assessment:AssessmentService');
    }

    /**
     * @return AnswerSceneService
     */
    protected function getAnswerSceneService()
    {
        return $this->getBiz()->service('ItemBank:Answer:AnswerSceneService');
    }

    /**
     * @return AnswerRecordService
     */
    protected function getAnswerRecordService()
    {
        return $this->getBiz()->service('ItemBank:Answer:AnswerRecordService');
    }

    /**
     * @return AnswerReportService
     */
    protected function getAnswerReportService()
    {
        return $this->getBiz()->service('ItemBank:Answer:AnswerReportService');
    }
}
