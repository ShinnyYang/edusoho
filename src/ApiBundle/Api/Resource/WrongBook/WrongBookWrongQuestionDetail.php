<?php

namespace ApiBundle\Api\Resource\WrongBook;

use ApiBundle\Api\ApiRequest;
use ApiBundle\Api\Resource\AbstractResource;
use AppBundle\Common\ArrayToolkit;
use Biz\Question\Traits\QuestionFormulaImgTrait;
use Biz\User\Service\UserService;
use Biz\WrongBook\Service\WrongQuestionService;
use Biz\WrongBook\WrongBookException;
use Codeages\Biz\ItemBank\Answer\Service\AnswerQuestionReportService;
use Codeages\Biz\ItemBank\Item\Service\ItemService;

class WrongBookWrongQuestionDetail extends AbstractResource
{
    use QuestionFormulaImgTrait;

    public function search(ApiRequest $request, $targetType, $itemId)
    {
        if (!in_array($targetType, ['course', 'classroom', 'exercise'])) {
            throw WrongBookException::WRONG_QUESTION_TARGET_TYPE_REQUIRE();
        }
        $orderBy = ['submit_time' => 'DESC'];
        $conditions = $this->prepareConditions($request->query->all(), $targetType, $itemId);
        list($offset, $limit) = $this->getOffsetAndLimit($request);

        $wrongQuestionsByUser = $this->getWrongQuestionService()->searchWrongQuestionsWithDistinctUserId($conditions, $orderBy, $offset, $limit);
        $wrongQuestionsByUser = $this->makeWrongQuestionDetailInfo($wrongQuestionsByUser);
        $questionsCount = $this->getWrongQuestionService()->countWrongQuestionsWithDistinctUserId($conditions);

        $itemInfo = $this->getItemService()->getItemWithQuestions($itemId, true);
        $itemInfo = $this->convertFormulaToImg($itemInfo);

        return array_merge(['item' => $itemInfo], $this->makePagingObject($wrongQuestionsByUser, $questionsCount, $offset, $limit));
    }

    protected function makeWrongQuestionDetailInfo($wrongQuestionsByUser)
    {
        $answerQuestionReportIds = ArrayToolkit::column($wrongQuestionsByUser, 'answer_question_report_id');
        $userIds = ArrayToolkit::column($wrongQuestionsByUser, 'user_id');
        $reports = $this->getAnswerQuestionReportService()->findByIds($answerQuestionReportIds);
        $users = $this->getUserService()->findUsersByIds($userIds);
        $detailInfo = [];
        foreach ($wrongQuestionsByUser as $question) {
            $detailInfo[] = $this->generateDetailData($question, $users, $reports);
        }

        return $detailInfo;
    }

    protected function generateDetailData($question, $users, $reports)
    {
        return [
            'id' => $question['id'],
            'user_id' => $question['user_id'],
            'user_name' => $users[$question['user_id']]['nickname'],
            'answer_time' => $question['submit_time'],
            'wrong_times' => $question['wrongTimes'],
            'answer' => 'no_answer' === $reports[$question['answer_question_report_id']]['status'] ? [] : $reports[$question['answer_question_report_id']]['response'],
        ];
    }

    protected function prepareConditions($conditions, $targetType, $itemId)
    {
        if (empty($conditions['targetId'])) {
            throw WrongBookException::WRONG_QUESTION_DATA_FIELDS_MISSING();
        }

        $prepareConditions = [];
        $pool = 'wrong_question.'.$targetType.'_pool';
        $prepareConditions['answer_scene_ids'] = $this->biz[$pool]->prepareSceneIdsByTargetId($conditions['targetId'], $conditions);
        $prepareConditions['item_id'] = $itemId;

        if ('exercise' === $targetType && 'chapter' === $conditions['exerciseMediaType'] && !empty($conditions['chapterId'])) {
            $childrenIds = $this->getItemCategoryService()->findCategoryChildrenIds($conditions['chapterId']);
            $prepareConditions['testpaper_ids'] = array_merge([$conditions['chapterId']], $childrenIds);
        }
        if ('exercise' === $targetType && 'testpaper' === $conditions['exerciseMediaType'] && !empty($conditions['testpaperId'])) {
            $prepareConditions['testpaper_id'] = $conditions['testpaperId'];
        }

        return $prepareConditions;
    }

    /**
     * @return WrongQuestionService
     */
    private function getWrongQuestionService()
    {
        return $this->service('WrongBook:WrongQuestionService');
    }

    /**
     * @return AnswerQuestionReportService
     */
    protected function getAnswerQuestionReportService()
    {
        return $this->service('ItemBank:Answer:AnswerQuestionReportService');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->getBiz()->service('User:UserService');
    }

    /**
     * @return ItemService
     */
    protected function getItemService()
    {
        return $this->service('ItemBank:Item:ItemService');
    }
}
