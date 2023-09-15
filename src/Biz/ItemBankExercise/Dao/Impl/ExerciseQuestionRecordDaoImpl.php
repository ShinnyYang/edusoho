<?php

namespace Biz\ItemBankExercise\Dao\Impl;

use Biz\ItemBankExercise\Dao\ExerciseQuestionRecordDao;
use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;

class ExerciseQuestionRecordDaoImpl extends AdvancedDaoImpl implements ExerciseQuestionRecordDao
{
    protected $table = 'item_bank_exercise_question_record';

    public function findByUserIdAndExerciseId($userId, $exerciseId)
    {
        return $this->findByFields(['userId' => $userId, 'exerciseId' => $exerciseId]);
    }

    public function deleteByExerciseId($exerciseId)
    {
        return $this->db()->delete($this->table(), ['exerciseId' => $exerciseId]);
    }

    public function countQuestionRecordStatus($exerciseId, $itemIds)
    {
        if (empty($itemIds)) {
            return [];
        }
        $marks = str_repeat('?,', count($itemIds) - 1).'?';
        $sql = "SELECT userId, `status`, count(*) AS num from {$this->table} WHERE exerciseId = ? and itemId IN ({$marks}) GROUP BY userId, `status`;";

        return $this->db()->fetchAll($sql, array_merge([$exerciseId], $itemIds));
    }

    public function declares()
    {
        return [
            'timestamps' => ['createdTime', 'updatedTime'],
            'orderbys' => ['createdTime'],
            'conditions' => [
                'exerciseId = :exerciseId',
                'itemBankId = :itemBankId',
                'questionId IN (:questionIds)',
                'itemId IN (:itemIds)',
            ],
        ];
    }
}
