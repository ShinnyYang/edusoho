<?php

namespace Biz\WrongBook\Pool;

use AppBundle\Common\ArrayToolkit;
use Biz\Activity\Service\ActivityService;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\CourseSetService;
use Biz\Task\Service\TaskService;
use Biz\WrongBook\Dao\WrongQuestionBookPoolDao;
use Biz\WrongBook\Dao\WrongQuestionCollectDao;
use Biz\WrongBook\Service\WrongQuestionService;

class CoursePool extends AbstractPool
{
    public function getPoolTarget($report)
    {
        // TODO: Implement getPoolTarget() method.
    }

    public function prepareSceneIds($poolId, $conditions)
    {
        $pool = $this->getWrongQuestionBookPoolDao()->get($poolId);
        if (empty($pool) || 'course' != $pool['target_type']) {
            return [];
        }

        if (empty($conditions['courseId']) && empty($conditions['courseMediaType']) && empty($conditions['courseTaskId'])) {
            return [];
        }

        return $this->prepareCommonSceneIds($conditions, $pool['target_id']);
    }

    public function prepareSceneIdsByTargetId($targetId, $conditions)
    {
        $this->getCourseService()->tryManageCourse($conditions['courseId']);

        $conditions = array_merge($conditions, [
            'courseId' => $conditions['courseId'],
        ]);

        return $this->prepareCommonSceneIds($conditions, $targetId);
    }

    public function prepareCommonSceneIds($conditions, $targetId)
    {
        $sceneIds = $this->findSceneIdsByCourseSetId($targetId);

        if (!empty($conditions['courseId'])) {
            $sceneIdsByCourseId = $this->findSceneIdsByCourseId($conditions['courseId']);
            $sceneIds = empty($sceneIds) ? $sceneIdsByCourseId : array_intersect($sceneIds, $sceneIdsByCourseId);
        }

        if (!empty($conditions['courseMediaType'])) {
            $sceneIdsByCourseMediaType = $this->findSceneIdsByCourseMediaType($targetId, $conditions['courseMediaType']);
            $sceneIds = empty($sceneIds) ? $sceneIdsByCourseMediaType : array_intersect($sceneIds, $sceneIdsByCourseMediaType);
        }

        if (!empty($conditions['courseTaskId'])) {
            $sceneIdsByCourseTaskId = $this->findSceneIdsByCourseTaskId($conditions['courseTaskId']);
            $sceneIds = empty($sceneIds) ? $sceneIdsByCourseTaskId : array_intersect($sceneIds, $sceneIdsByCourseTaskId);
        }

        return empty($sceneIds) ? [-1] : $sceneIds;
    }

    public function buildConditions($pool, $conditions)
    {
        $courseSet = $this->getCourseSetService()->getCourseSet($pool['target_id']);
        $courses = $this->getCourseService()->findPublishedCoursesByCourseSetId($pool['target_id']);
        $conditions['courseIds'] = ArrayToolkit::column($courses, 'id');
        $conditions = $this->handleConditions($conditions);
        $tasks = $this->getCourseTaskService()->searchTasks($conditions, [], 0, PHP_INT_MAX);

        $collects = $this->getWrongQuestionCollectDao()->findCollectBYPoolId($pool['id']);
        $collectIds = array_unique(ArrayToolkit::column($collects, 'id'));
        $wrongQuestions = $this->getWrongQuestionService()->searchWrongQuestion(['collect_ids' => $collectIds], [], 0, PHP_INT_MAX);
        $answerSceneIds = array_unique(ArrayToolkit::column($wrongQuestions, 'answer_scene_id'));

        $activitys = [];
        $allActivitys = [];
        foreach ($answerSceneIds as $answerSceneId) {
            $activity = $this->getActivityService()->getActivityByAnswerSceneId($answerSceneId);
            $allActivitys[] = $activity;
            if (isset($conditions['courseId']) && $conditions['courseId'] != $activity['fromCourseId']) {
                continue;
            }
            $activitys[] = $activity;
        }
        $coursesIds = array_unique(ArrayToolkit::column($allActivitys, 'fromCourseId'));

        $courses = ArrayToolkit::index($courses, 'id');
        $tasks = ArrayToolkit::index($tasks, 'activityId');
        $activityIds = ArrayToolkit::column($activitys, 'id');
        $taskTypes = array_values(array_unique(ArrayToolkit::column($activitys, 'mediaType')));

        $newCourses = [];
        foreach ($coursesIds as $coursesId) {
            if (!empty($courses[$coursesId])) {
                $newCourses[] = $courses[$coursesId];
            }
        }
        $newTasks = [];
        foreach ($activityIds as $activityId) {
            if (!empty($tasks[$activityId])) {
                $newTasks[] = $tasks[$activityId];
            }
        }

        $result['plans'] = $this->handleArray($newCourses, ['id', 'title']);
        $result['source'] = $taskTypes;
        $result['tasks'] = $this->handleArray($newTasks, ['id', 'title']);
        $result['title'] = $courseSet['title'];

        return $result;
    }

    public function buildTargetConditions($targetId, $conditions)
    {
        $conditions = $this->handleConditions($conditions);
        $tasks = $this->getCourseTaskService()->searchTasks($conditions, [], 0, PHP_INT_MAX);
        $pools = $this->getWrongQuestionService()->searchWrongBookPool(['target_type' => 'course', 'target_id' => $targetId], [], 0, PHP_INT_MAX);
        if(empty($pools)) {
            return (object)[];
        }
        $poolIds = empty($pools) ? [-1] : ArrayToolkit::column($pools, 'id');

        $collects = $this->getWrongQuestionCollectDao()->search(['pool_ids' => $poolIds], [], 0, PHP_INT_MAX);
        $collectIds = array_unique(ArrayToolkit::column($collects, 'id'));
        $wrongQuestions = $this->getWrongQuestionService()->searchWrongQuestion(['collect_ids' => $collectIds], [], 0, PHP_INT_MAX);
        $answerSceneIds = array_unique(ArrayToolkit::column($wrongQuestions, 'answer_scene_id'));

        $activitys = [];
        foreach ($answerSceneIds as $answerSceneId) {
            $activity = $this->getActivityService()->getActivityByAnswerSceneId($answerSceneId);
            if (isset($conditions['courseId']) && $conditions['courseId'] != $activity['fromCourseId']) {
                continue;
            }
            $activitys[] = $activity;
        }
        $tasks = ArrayToolkit::index($tasks, 'activityId');
        $activityIds = ArrayToolkit::column($activitys, 'id');
        $taskTypes = array_values(array_unique(ArrayToolkit::column($activitys, 'mediaType')));

        $newTasks = [];
        foreach ($activityIds as $activityId) {
            if (!empty($tasks[$activityId])) {
                $newTasks[] = $tasks[$activityId];
            }
        }

        $result['source'] = $taskTypes;
        $result['tasks'] = $this->handleArray($newTasks, ['id', 'title']);

        return $result;
    }

    protected function handleArray($data, $fields, $type = '')
    {
        $newData = [];
        foreach ($data as $key => $value) {
            foreach ($fields as $k => $field) {
                $newData[$key][$field] = (empty($value[$field]) && isset($value['courseSetTitle'])) ? $value['courseSetTitle'] : $value[$field];
            }
        }

        return $newData;
    }

    protected function handleConditions($conditions)
    {
        if (empty($conditions['courseId'])) {
            unset($conditions['courseId']);
        } else {
            unset($conditions['courseIds']);
        }
        if (!empty($conditions['courseMediaType'])) {
            $conditions['type'] = $conditions['courseMediaType'];
            unset($conditions['courseMediaType']);
        } else {
            $conditions['types'] = ['testpaper', 'exercise', 'homework'];
        }

        return $conditions;
    }

    protected function findSceneIdsByCourseSetId($courseSetId)
    {
        $activityTestPapers = $this->getActivityService()->findActivitiesByCourseSetIdAndType($courseSetId, 'testpaper', true);
        $activityHomeWorks = $this->getActivityService()->findActivitiesByCourseSetIdAndType($courseSetId, 'homework', true);
        $activityExercises = $this->getActivityService()->findActivitiesByCourseSetIdAndType($courseSetId, 'exercise', true);
        $activates = array_merge($activityTestPapers, $activityHomeWorks, $activityExercises);

        return $this->generateSceneIds($activates);
    }

    protected function findSceneIdsByCourseId($courseId)
    {
        $activityTestPapers = $this->getActivityService()->findActivitiesByCourseIdAndType($courseId, 'testpaper', true);
        $activityHomeWorks = $this->getActivityService()->findActivitiesByCourseIdAndType($courseId, 'homework', true);
        $activityExercises = $this->getActivityService()->findActivitiesByCourseIdAndType($courseId, 'exercise', true);
        $activates = array_merge($activityTestPapers, $activityHomeWorks, $activityExercises);

        return $this->generateSceneIds($activates);
    }

    protected function findSceneIdsByCourseMediaType($targetId, $mediaType)
    {
        if (!in_array($mediaType, ['testpaper', 'homework', 'exercise'])) {
            return [];
        }

        $activates = $this->getActivityService()->findActivitiesByCourseSetIdAndType($targetId, $mediaType, true);

        return $this->generateSceneIds($activates);
    }

    protected function findSceneIdsByCourseTaskId($courseTaskId)
    {
        $courseTask = $this->getCourseTaskService()->getTask($courseTaskId);
        if (empty($courseTask)) {
            return [];
        }
        $activity = $this->getActivityService()->getActivity($courseTask['activityId'], true);

        return $this->generateSceneIds([$activity]);
    }

    protected function generateSceneIds($activates)
    {
        $sceneIds = [];
        array_walk($activates, function ($activity) use (&$sceneIds) {
            if (!empty($activity['ext'])) {
                $sceneIds[] = $activity['ext']['answerSceneId'];
            }
        });

        return $sceneIds;
    }

    /**
     * @return WrongQuestionBookPoolDao
     */
    protected function getWrongQuestionBookPoolDao()
    {
        return $this->biz->dao('WrongBook:WrongQuestionBookPoolDao');
    }

    /**
     * @return WrongQuestionCollectDao
     */
    protected function getWrongQuestionCollectDao()
    {
        return $this->biz->dao('WrongBook:WrongQuestionCollectDao');
    }

    /**
     * @return WrongQuestionService
     */
    protected function getWrongQuestionService()
    {
        return $this->biz->service('WrongBook:WrongQuestionService');
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return  $this->biz->service('Activity:ActivityService');
    }

    /**
     * @return TaskService
     */
    protected function getCourseTaskService()
    {
        return $this->biz->service('Task:TaskService');
    }

    /**
     * @return CourseService
     */
    private function getCourseService()
    {
        return $this->biz->service('Course:CourseService');
    }

    /**
     * @return CourseSetService
     */
    private function getCourseSetService()
    {
        return $this->biz->service('Course:CourseSetService');
    }
}
