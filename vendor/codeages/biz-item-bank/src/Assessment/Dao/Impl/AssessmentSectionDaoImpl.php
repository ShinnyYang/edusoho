<?php

namespace Codeages\Biz\ItemBank\Assessment\Dao\Impl;

use Codeages\Biz\ItemBank\Assessment\Dao\AssessmentSectionDao;
use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;

class AssessmentSectionDaoImpl extends AdvancedDaoImpl implements AssessmentSectionDao
{
    protected $table = 'biz_assessment_section';

    public function findByAssessmentId($assessmentId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE assessment_id = ? order by seq ASC, id ASC";

        return $this->db()->fetchAll($sql, [$assessmentId]);
    }

    public function deleteByAssessmentId($assessmentId)
    {
        $sql = "DELETE FROM {$this->table} WHERE assessment_id = ?";

        return $this->db()->executeUpdate($sql, [$assessmentId]);
    }

    public function findByAssessmentIds($assessmentIds)
    {
        return $this->findInField('assessment_id', $assessmentIds);
    }

    public function declares()
    {
        return array(
            'orderbys' => [
                'id',
                'created_time',
                'assessment_id',
                'seq',
            ],
            'timestamps' => [
                'created_time',
                'updated_time',
            ],
            'conditions' => [
                'id = :id',
                'id in (:ids)',
                'assessment_id in (:assessmentIds)',
            ],
        );
    }
}
