<?php

namespace Biz\Course\Dao;

use Codeages\Biz\Framework\Dao\GeneralDaoInterface;

interface CourseSetDao extends GeneralDaoInterface
{
    const TABLE_NAME = 'course_set_v8';

    public function findByIds(array $ids);

    public function findLikeTitle($title);

    public function findCourseSetsByCategoryIdAndCreator($categoryId, $creator);

    public function findProductIdAndGoodsIdsByIds($ids);

    public function findCourseSetsByParentIdAndLocked($parentId, $locked);

    public function analysisCourseSetDataByTime($startTime, $endTime);

    public function refreshHotSeq();

    public function searchCourseSetsByTeacherOrderByStickTime($conditions, $orderBy, $userId, $start, $limit);

    public function banLearningByIds($ids);

    public function hideByIds($ids);

    public function canLearningByIds($ids);

    public function showByIds($ids);
}
