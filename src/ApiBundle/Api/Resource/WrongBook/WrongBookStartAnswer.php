<?php

namespace ApiBundle\Api\Resource\WrongBook;

use ApiBundle\Api\ApiRequest;
use ApiBundle\Api\Resource\AbstractResource;
use ApiBundle\Api\Resource\Assessment\AssessmentFilter;
use AppBundle\Common\ArrayToolkit;
use Biz\WrongBook\Service\WrongQuestionService;
use Biz\WrongBook\WrongBookException;
use Codeages\Biz\ItemBank\Answer\Constant\ExerciseMode;
use Codeages\Biz\ItemBank\Answer\Service\AnswerRecordService;
use Codeages\Biz\ItemBank\Answer\Service\AnswerSceneService;
use Codeages\Biz\ItemBank\Answer\Service\AnswerService;
use Codeages\Biz\ItemBank\Assessment\Exception\AssessmentException;
use Codeages\Biz\ItemBank\Assessment\Service\AssessmentService;
use Codeages\Biz\ItemBank\Item\Service\ItemCategoryService;
use Codeages\Biz\ItemBank\Item\Service\ItemService;

class WrongBookStartAnswer extends AbstractResource
{
    /**
     * @param $poolId
     */
    public function add(ApiRequest $request, $poolId)
    {
        $pool = $this->getWrongQuestionService()->getPool($poolId);
        $conditions = $request->query->all();
        $conditions['targetType'] = $pool['target_type'];
        if (empty($conditions['exerciseMediaType']) && !empty($request->request->get('exerciseMediaType'))) {
            $conditions['exerciseMediaType'] = $request->request->get('exerciseMediaType');
        }
        $filterConditions = $this->prepareConditions($poolId, $conditions);
        $wrongQuestionsCount = $this->getWrongQuestionService()->countWrongQuestionWithCollect($filterConditions);

        $itemNum = $request->request->get('itemNum', min($wrongQuestionsCount, 20));
        if ($wrongQuestionsCount < $itemNum) {
            throw WrongBookException::WRONG_QUESTION_NUM_LIMIT();
        }

        list($orderBy, $start) = $this->getSearchFields($wrongQuestionsCount, $itemNum);
        $wrongQuestions = $this->getWrongQuestionService()->searchWrongQuestionsWithCollect($filterConditions, $orderBy, $start, $itemNum);

        $itemIds = ArrayToolkit::column($wrongQuestions, 'item_id');
        $items = $this->getItemService()->findItemsByIds($itemIds, true);
        $answerScene = $this->initScene($pool);
        $assessment = [
            'name' => '错题练习',
            'displayable' => 0,
            'description' => '',
            'bank_id' => 0,
            'sections' => [
                [
                    'name' => '题目列表',
                    'items' => $items,
                ],
            ],
        ];

        $assessment = $this->getAssessmentService()->createAssessment($assessment);

        $this->getAssessmentService()->openAssessment($assessment['id']);

        $exerciseMode = $request->request->get('exerciseMode', ExerciseMode::SUBMIT_ALL);
        $answerRecord = $this->getAnswerService()->startAnswer($answerScene['id'], $assessment['id'], $this->getCurrentUser()['id']);
        $answerRecord = $this->getAnswerRecordService()->update($answerRecord['id'], ['exercise_mode' => $exerciseMode]);

        $assessment = $this->getAssessmentService()->showAssessment($answerRecord['assessment_id']);

        if (empty($assessment)) {
            throw AssessmentException::ASSESSMENT_NOTEXIST();
        }
        if ('open' !== $assessment['status']) {
            throw AssessmentException::ASSESSMENT_NOTOPEN();
        }

        $assessmentFilter = new AssessmentFilter();
        $assessmentFilter->filter($assessment);

        return [
            'assessment' => $assessment,
            'assessment_response' => $this->getAnswerService()->getAssessmentResponseByAnswerRecordId($answerRecord['id']),
            'answer_scene' => $this->getAnswerSceneService()->get($answerRecord['answer_scene_id']),
            'answer_record' => $answerRecord,
        ];
    }

    protected function prepareConditions($poolId, $conditions)
    {
        $prepareConditions = [];
        $prepareConditions['pool_id'] = $poolId;
        $prepareConditions['status'] = 'wrong';
        $prepareConditions['user_id'] = $this->getCurrentUser()->getId();

        if (!in_array($conditions['targetType'], ['course', 'classroom', 'exercise'])) {
            throw WrongBookException::WRONG_QUESTION_TARGET_TYPE_REQUIRE();
        }

        $pool = 'wrong_question.'.$conditions['targetType'].'_pool';
        $prepareConditions['answer_scene_ids'] = $this->biz[$pool]->prepareSceneIds($poolId, $conditions);

        if ('exercise' === $conditions['targetType'] && 'chapter' === $conditions['exerciseMediaType'] && !empty($conditions['chapterId'])) {
            $childrenIds = $this->getItemCategoryService()->findCategoryChildrenIds($conditions['chapterId']);
            $prepareConditions['testpaper_ids'] = array_merge([$conditions['chapterId']], $childrenIds);
        }
        if ('exercise' === $conditions['targetType'] && 'testpaper' === $conditions['exerciseMediaType'] && !empty($conditions['testpaperId'])) {
            $prepareConditions['testpaper_id'] = $conditions['testpaperId'];
        }

        return $prepareConditions;
    }

    protected function getSearchFields($count, $limitNum)
    {
        $regularWrongTimes = [
            ['wrong_times' => 'DESC'],
            ['wrong_times' => 'ASC'],
        ];
        $regularUpdatedTime = [
            ['last_submit_time' => 'DESC'],
            ['last_submit_time' => 'ASC'],
        ];
        $regularEmpty = [
            [],
            [],
        ];

        $orderBys = [$regularWrongTimes, $regularUpdatedTime, $regularEmpty];
        $orderBy = $orderBys[mt_rand(0, 2)][mt_rand(0, 1)];

        if ($count > $limitNum) {
            $start = mt_rand(0, $count - $limitNum);
        } else {
            $start = 0;
        }

        return [
            $orderBy,
            $start,
        ];
    }

    protected function initScene($pool)
    {
        if (empty($pool['scene_id'])) {
            $answerScene = $this->getAnswerSceneService()->create([
                'name' => '错题练习',
                'limited_time' => 0,
                'do_times' => 0,
                'redo_interval' => 0,
                'need_score' => 0,
                'manual_marking' => 0,
                'start_time' => 0,
            ]);
            $this->getWrongQuestionService()->updatePool($pool['id'], ['scene_id' => $answerScene['id']]);
        } else {
            $answerScene = $this->getAnswerSceneService()->get($pool['scene_id']);
        }

        return $answerScene;
    }

    /**
     * @return AssessmentService
     */
    protected function getAssessmentService()
    {
        return $this->getBiz()->service('WrongBook:WrongBookAssessmentService');
    }

    /**
     * @return AnswerService
     */
    protected function getAnswerService()
    {
        return $this->getBiz()->service('ItemBank:Answer:AnswerService');
    }

    /**
     * @return AnswerSceneService
     */
    protected function getAnswerSceneService()
    {
        return $this->getBiz()->service('ItemBank:Answer:AnswerSceneService');
    }

    /**
     * @return WrongQuestionService
     */
    protected function getWrongQuestionService()
    {
        return $this->getBiz()->service('WrongBook:WrongQuestionService');
    }

    /**
     * @return ItemService
     */
    protected function getItemService()
    {
        return $this->getBiz()->service('ItemBank:Item:ItemService');
    }

    /**
     * @return ItemCategoryService
     */
    protected function getItemCategoryService()
    {
        return $this->service('ItemBank:Item:ItemCategoryService');
    }

    /**
     * @return AnswerRecordService
     */
    protected function getAnswerRecordService()
    {
        return $this->service('ItemBank:Answer:AnswerRecordService');
    }
}
