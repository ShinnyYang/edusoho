<?php

namespace Biz\ItemBankExercise\Service;

interface AssessmentExerciseService
{
    public function findByModuleId($moduleId);

    public function findByModuleIds($moduleIds);

    public function findByExerciseIdAndModuleId($exerciseId, $moduleId);

    public function getByModuleIdAndAssessmentId($moduleId, $assessmentId);

    public function search($conditions, $sort, $start, $limit, $columns = []);

    public function count($conditions);

    public function startAnswer($moduleId, $assessmentId, $userId);

    public function addAssessments($exerciseId, $moduleId, $assessments);

    public function isAssessmentExercise($moduleId, $assessmentId, $exerciseId);

    public function deleteAssessmentExercise($id);

    public function batchDeleteAssessmentExercise($ids);

    public function getAssessmentCountGroupByExerciseId($ids);

    public function deleteByAssessmentId($assessmentId);

    public function deleteByAssessmentIds($assessmentIds);
}
