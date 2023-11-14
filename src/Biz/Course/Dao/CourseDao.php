<?php

namespace Biz\Course\Dao;

use Codeages\Biz\Framework\Dao\GeneralDaoInterface;

interface CourseDao extends GeneralDaoInterface
{
    const TABLE_NAME = 'course_v8';

    public function findCoursesByCourseSetIdAndStatus($courseSetId, $status);

    public function getDefaultCourseByCourseSetId($courseSetId);

    public function getDefaultCoursesByCourseSetIds($courseSetIds);

    public function findByCourseSetIds(array $setIds);

    public function findPriceIntervalByCourseSetIds($courseSetIds);

    public function findCoursesByIds($ids);

    public function findCoursesByIdsAndCourseSetTitleLike($ids, $title);

    public function findCoursesByCategoryIds($categoryIds);

    public function findCourseSetIncomesByCourseSetIds(array $courseSetIds);

    public function sumTotalIncomeByIds(array $ids);

    public function analysisCourseDataByTime($startTime, $endTime);

    public function findCoursesByParentIdAndLocked($parentId, $locked);

    public function findCoursesByParentIds($parentIds);

    public function findProductIdAndGoodsIdAndSpecsIdByIds($ids);

    public function getMinAndMaxPublishedCoursePriceByCourseSetId($courseSetId);

    public function updateMaxRateByCourseSetId($courseSetId, $updateFields);

    public function updateCourseRecommendByCourseSetId($courseSetId, $fields);

    public function updateCategoryByCourseSetId($courseSetId, $fields);

    public function updateByCourseSetId($courseSetId, $fields);

    public function showByCourseSetId($courseSetId);

    public function canLearningByCourseSetId($courseSetId);

    public function countGroupByCourseSetIds($courseSetIds);

    public function searchWithJoinCourseSet($conditions, $orderBys, $start, $limit);

    public function searchByStudentNumAndTimeZone($conditions, $start, $limit);

    public function searchByRatingAndTimeZone($conditions, $start, $limit);

    public function countWithJoinCourseSet($conditions);

    public function findCourseByCourseSetTitleLike($courseSetTitle);

    public function showByCourseSetIds($courseSetIds);

    public function hideByCourseSetIds($courseSetIds);

    public function canLearningByCourseSetIds($courseSetIds);

    public function banLearningByCourseSetIds($courseSetIds);
}
