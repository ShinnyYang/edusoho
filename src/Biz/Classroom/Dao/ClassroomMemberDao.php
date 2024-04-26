<?php

namespace Biz\Classroom\Dao;

use Codeages\Biz\Framework\Dao\AdvancedDaoInterface;

interface ClassroomMemberDao extends AdvancedDaoInterface
{
    public function searchMembersByClassroomId($classroomId, $conditions, $start, $limit);

    public function countMembersByClassroomId($classroomId, $conditions);

    public function countStudents($classroomId);

    public function countAuditors($classroomId);

    public function getByClassroomIdAndUserId($classroomId, $userId);

    public function deleteByClassroomIdAndUserId($classroomId, $userId);

    public function findTeachersByClassroomId($classroomId);

    public function findAssistantsByClassroomId($classroomId);

    public function findByUserIdAndClassroomIds($userId, array $classroomIds);

    public function findByClassroomIdAndRole($classroomId, $role, $start, $limit);

    public function findByClassroomIdAndUserIds($classroomId, $userIds);

    public function findByUserId($userId);

    public function countMobileFilledMembersByClassroomId($classroomId, $userLocked = 0);

    public function updateMembers($conditions, $updateFields);

    public function changeMembersDeadlineByClassroomId($classroomId, $day);

    public function updateByClassroomIdAndRole($classroomId, $role, array $fields);

    public function findMembersByUserIdAndClassroomIds($userId, array $classroomIds);

    public function findMembersByUserId($userId);

    public function searchMemberCountGroupByFields($conditions, $groupBy, $start, $limit);

    public function searchSignStatisticsByClassroomId($classroomId, array $conditions, array $orderBys, $start, $limit);

    public function findDailyIncreaseDataByClassroomIdAndRoleWithTimeRange($classroomId, $role, $startTime, $endTime, $format = '%Y-%m-%d');
}
