<?php

namespace Biz\Review\Service;

interface ReviewService
{
    public function getReview($id);

    public function createReview($review);

    public function tryCreateReview($review);

    public function updateReview($id, $review);

    public function deleteReview($id);

    public function deleteReviewsByUserId($userId);

    public function countReviews($conditions);

    public function searchReviews($conditions, $orderBys, $start, $limit, $columns = []);

    public function countRatingByTargetTypeAndTargetId($targetType, $targetId);

    public function countRatingByTargetTypeAndTargetIds($targetType, $targetIds);

    public function getReviewByUserIdAndTargetTypeAndTargetId($userId, $targetType, $targetId);

    public function countCourseReviews($conditions);

    public function searchCourseReviews($conditions, $orderBys, $start, $limit);

    public function countClassroomReviews($conditions);

    public function searchClassroomReviews($conditions, $orderBys, $start, $limit);

    public function canReviewBySelf($reportId, $userId);
}
