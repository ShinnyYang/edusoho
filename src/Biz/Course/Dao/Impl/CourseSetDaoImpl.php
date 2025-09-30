<?php

namespace Biz\Course\Dao\Impl;

use Biz\Course\Dao\CourseSetDao;
use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;

class CourseSetDaoImpl extends AdvancedDaoImpl implements CourseSetDao
{
    protected $table = 'course_set_v8';

    public function findCourseSetsByParentIdAndLocked($parentId, $locked)
    {
        return $this->findByFields(['parentId' => $parentId, 'locked' => $locked]);
    }

    public function findProductIdAndGoodsIdsByIds($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $marks = str_repeat('?,', count($ids) - 1).'?';
        $sql = "SELECT csv8.id AS courseSetId, p.id AS productId, g.id AS goodsId  FROM {$this->table} csv8 
                LEFT JOIN `product` p ON csv8.id=p.targetId AND p.targetType='course'
                LEFT JOIN `goods` g ON g.productId=p.id
                WHERE csv8.id in ({$marks});";

        return $this->db()->fetchAll($sql, $ids);
    }

    public function findByIds(array $ids)
    {
        return $this->findInField('id', $ids);
    }

    public function findLikeTitle($title)
    {
        if (empty($title)) {
            $title = '';
        }
        $title = '%'.$title.'%';
        $sql = "SELECT * FROM {$this->table} WHERE title LIKE ?";

        return $this->db()->fetchAll($sql, [$title]);
    }

    public function findCourseSetsByCategoryIdAndCreator($categoryId, $creator)
    {
        return $this->findByFields(['parentId' => $categoryId, 'creator' => $creator]);
    }

    public function searchCourseSetsByTeacherOrderByStickTime($conditions, $orderBy, $userId, $start, $limit)
    {
        $courseSetAlias = 'course_set_v8'; //course_set_v8
        foreach ($conditions as $key => $condition) {
            $conditions[$courseSetAlias.'_'.$key] = $condition;
            unset($conditions[$key]);
        }
        $builder = $this->createQueryBuilder($conditions)
            ->select("{$courseSetAlias}.*, max(course_member.stickyTime) as stickyTime, course_member.courseSetId")
            ->setFirstResult($start)
            ->setMaxResults($limit)
            ->innerJoin($courseSetAlias, 'course_member', 'course_member', "course_member.courseSetId={$courseSetAlias}.id")
            ->addOrderBy('stickyTime', 'DESC')
            ->groupBy('course_member.courseSetId')
            ->andWhere("{$courseSetAlias}.status = :{$courseSetAlias}_status")
            ->andWhere("{$courseSetAlias}.parentId = :{$courseSetAlias}_parentId")
            ->andWhere("{$courseSetAlias}.type NOT IN (:{$courseSetAlias}_excludeTypes)")
            ->andStaticWhere("course_member.role in ('teacher', 'assistant')")
            ->andStaticWhere("course_member.userId = {$userId}");

        foreach ($orderBy ?: [] as $order => $sort) {
            $builder->addOrderBy($order, $sort);
        }

        return $builder->execute()->fetchAll();
    }

    public function banLearningByIds($ids)
    {
        $ids = implode(',', $ids);
        $sql = "UPDATE {$this->table} set canLearn = '0' where id in ({$ids})";
        $this->db()->executeQuery($sql);
    }

    public function canLearningByIds($ids)
    {
        $ids = implode(',', $ids);
        $sql = "UPDATE {$this->table} set canLearn = '1' where id in ({$ids}) and status = 'published'";
        $this->db()->executeQuery($sql);
    }

    public function analysisCourseSetDataByTime($startTime, $endTime)
    {
        $conditions = [
            'startTime' => $startTime,
            'endTime' => $endTime,
            'parentId' => 0,
        ];
        $builder = $this->createQueryBuilder($conditions)
            ->select("COUNT(id) as count, from_unixtime(createdTime, '%Y-%m-%d') as date")
            ->from($this->table, $this->table)
            ->groupBy("from_unixtime(createdTime,'%Y-%m-%d')")
            ->addOrderBy('DATE', 'ASC');

        return $builder->execute()->fetchAll();
    }

    public function refreshHotSeq()
    {
        $sql = "UPDATE {$this->table} set hotSeq = 0;";
        $this->db()->exec($sql);
    }

    public function declares()
    {
        return [
            'conditions' => [
                'id IN ( :ids )',
                'id = :id',
                'status = :status',
                'status In (:includeStatus)',
                'isVip = :isVip',
                'categoryId = :categoryId',
                'categoryId IN (:categoryIds)',
                'title LIKE :title',
                'creator = :creator',
                'type = :type',
                'recommended = :recommended',
                'id NOT IN (:excludeIds)',
                'parentId = :parentId',
                'parentId in (:parentIds)',
                'parentId > :parentId_GT',
                'createdTime >= :startTime',
                'createdTime <= :endTime',
                'discountId = :discountId',
                'serializeMode = :serializeMode',
                'minCoursePrice = :minCoursePrice',
                'maxCoursePrice > :maxCoursePrice_GT',
                'updatedTime >= :updatedTime_GE',
                'updatedTime <= :updatedTime_LE',
                'minCoursePrice = :price',
                'orgCode PRE_LIKE :likeOrgCode',
                'type NOT IN (:excludeTypes)',
                'type IN (:types)',
                'locked = :locked',
                'platform = :platform',
                'isClassroomRef = :isClassroomRef',
            ],
            'serializes' => [
                'goals' => 'delimiter',
                'tags' => 'delimiter',
                'audiences' => 'delimiter',
                'teacherIds' => 'delimiter',
                'cover' => 'json',
            ],
            'orderbys' => [
                'createdTime',
                'updatedTime',
                'recommendedSeq',
                'hitNum',
                'recommendedTime',
                'rating',
                'studentNum',
                'id',
                'hotSeq',
                'publishedTime',
            ],
            'timestamps' => [
                'createdTime', 'updatedTime',
            ],
            'wave_cahceable_fields' => ['hitNum'],
        ];
    }
}
