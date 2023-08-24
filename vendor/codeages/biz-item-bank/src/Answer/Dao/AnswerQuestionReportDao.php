<?php
namespace Codeages\Biz\ItemBank\Answer\Dao;

interface AnswerQuestionReportDao
{
    public function findByIds($ids);

    public function findByAnswerRecordId($answerRecordId);

    public function deleteByAssessmentId($assessmentId);

    public function getByAnswerRecordIdAndQuestionId($answerRecordId, $questionId);
}
