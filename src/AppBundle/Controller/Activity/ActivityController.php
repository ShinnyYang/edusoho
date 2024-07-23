<?php

namespace AppBundle\Controller\Activity;

use AppBundle\Controller\BaseController;
use Biz\Activity\ActivityException;
use Biz\Activity\Service\ActivityService;
use Biz\Common\CommonException;
use Biz\Course\Service\CourseService;
use Symfony\Component\HttpFoundation\Request;

class ActivityController extends BaseController
{
    public function showAction($task, $preview)
    {
        $activity = $this->getActivityService()->getActivity($task['activityId'], true);

        if (empty($activity)) {
            $this->createNewException(ActivityException::NOTFOUND_ACTIVITY());
        }

        if ('video' == $activity['mediaType']) {
            $activity['watchStatus'] = $this->checkVideoWatchStatus($activity);
        }

        $activityConfigManage = $this->get('activity_config_manager');
        if ($activityConfigManage->isLtcActivity($activity['mediaType'])) {
            $container = $this->get('activity_runtime_container');
            $activity['preview'] = $preview;

            return $container->show($activity);
        }

        $actionConfig = $this->getActivityConfig($activity['mediaType']);

        return $this->forward($actionConfig['controller'].':show', [
            'activity' => $activity,
            'preview' => $preview,
        ]);
    }

    public function previewAction($task)
    {
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        if (empty($activity)) {
            $this->createNewException(ActivityException::NOTFOUND_ACTIVITY());
        }
        $actionConfig = $this->getActivityConfig($activity['mediaType']);

        return $this->forward($actionConfig['controller'].':preview', [
            'task' => $task,
        ]);
    }

    public function updateAction($id, $courseId)
    {
        $activity = $this->getActivityService()->getActivity($id);
        $actionConfig = $this->getActivityConfig($activity['mediaType']);

        return $this->forward($actionConfig['controller'].':edit', [
            'id' => $activity['id'],
            'courseId' => $courseId,
        ]);
    }

    public function createAction($type, $courseId)
    {
        $actionConfig = $this->getActivityConfig($type);

        return $this->forward($actionConfig['controller'].':create', [
            'courseId' => $courseId,
        ]);
    }

    public function contentModalAction($type, $courseId, $activityId = 0)
    {
        $course = $this->getCourseService()->tryManageCourse($courseId);
        if (!empty($activityId)) {
            $activity = $this->getActivityService()->getActivity($activityId, true);

            if (!empty($activity['ext']['roomType']) && 'pseudo' == $activity['ext']['roomType']) {
                $activity['mediaType'] = 'pseudolive';
                $activity['liveFile'] = json_decode(json_decode($activity['content'], true));
            }
        } else {
            $activity = [
                'id' => $activityId,
                'mediaType' => $type,
                'fromCourseId' => $courseId,
                'fromCourseSetId' => $course['courseSetId'],
                'customComments' => [],
                'canEdit' => 1,
            ];
        }

        $container = $this->get('activity_runtime_container');

        return $container->content($activity);
    }

    public function finishModalAction($activityId = 0, $type, $courseId)
    {
        $this->getCourseService()->tryManageCourse($courseId);
        if (!empty($activityId)) {
            $activity = $this->getActivityService()->getActivity($activityId);
        } else {
            $activity = [
                'id' => $activityId,
                'mediaType' => $type,
                'fromCourseId' => $courseId,
            ];
        }
        $activityConfigManage = $this->get('activity_config_manager');
        $config = $activityConfigManage->getInstalledActivity($type);

        return $this->render(
            'task-manage/create-or-update-finish.html.twig',
            [
                'activity' => $activity,
                'conditions' => empty($config['finish_condition']) ? [] : $config['finish_condition'],
            ]
        );
    }

    public function customManageRouteAction($fromCourseId, $mediaType, $id, $routeName)
    {
        $course = $this->getCourseService()->tryManageCourse($fromCourseId);
        $activity = [
            'id' => $id,
            'mediaType' => $mediaType,
            'fromCourseId' => $fromCourseId,
        ];

        $container = $this->get('activity_runtime_container');

        return $container->renderRoute($activity, $routeName);
    }

    public function customLearningRouteAction(Request $request, $courseId, $taskId)
    {
        $task = $this->getTaskService()->getTask($taskId);
        $activity = $this->getActivityService()->getActivity($task['activityId'], true);
        $container = $this->get('activity_runtime_container');
        $routeName = $request->query->get('routeName');

        return $container->renderRoute($activity, $routeName);
    }

    public function triggerAction(Request $request, $courseId, $activityId)
    {
        $this->getCourseService()->tryTakeCourse($courseId);

        $activity = $this->getActivityService()->getActivity($activityId);

        if (empty($activity)) {
            $this->createNewException(ActivityException::NOTFOUND_ACTIVITY());
        }

        $eventName = $request->request->get('eventName');

        if (empty($eventName)) {
            $this->createNewException(CommonException::ERROR_PARAMETER_MISSING());
        }

        $data = $request->request->get('data', []);

        $this->getActivityService()->trigger($activityId, $eventName, $data);

        return $this->createJsonResponse([
            'event' => $eventName,
            'data' => $data,
        ]);
    }

    protected function getActivityConfig($type)
    {
        $config = $this->get('extension.manager')->getActivities();

        return $config[$type];
    }

    protected function checkVideoWatchStatus($activity)
    {
        $isLimit = $this->setting('magic.lesson_watch_limit');
        if ($isLimit) {
            $type = $this->getActivityService()->getActivityConfig($activity['mediaType']);

            return $type->getWatchStatus($activity);
        }

        return ['status' => 'ok'];
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    /**
     * @return \Biz\Task\Service\TaskService
     */
    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }
}
