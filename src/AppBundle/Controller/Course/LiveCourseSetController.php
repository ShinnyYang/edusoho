<?php

namespace AppBundle\Controller\Course;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\Paginator;
use Biz\System\Service\SettingService;
use Biz\Task\Service\TaskService;
use Symfony\Component\HttpFoundation\Request;

class LiveCourseSetController extends CourseBaseController
{
    public function courseSetsBlockAction($courseSets, $view = 'list', $mode = 'default')
    {
        $courses = $this->getCourseService()->findCoursesByCourseSetIds(ArrayToolkit::column($courseSets, 'id'));

        return $this->forward(
            'AppBundle:Course/LiveCourseSet:coursesBlock',
            [
                'courses' => $courses,
                'view' => $view,
                'mode' => $mode,
            ]
        );
    }

    public function coursesBlockAction($courses, $view = 'list', $mode = 'default')
    {
        $userIds = [];

        foreach ($courses as $course) {
            $userIds = array_merge($userIds, empty($course['teacherIds']) ? [] : $course['teacherIds']);
        }

        $users = $this->getUserService()->findUsersByIds($userIds);

        foreach ($courses as &$course) {
            if (empty($course['id'])) {
                $course = [];
            }
        }

        $courses = array_filter($courses);

        return $this->render(
            "course/block/courses-block-{$view}.html.twig",
            [
                'courses' => $courses,
                'users' => $users,
                'mode' => $mode,
            ]
        );
    }

    public function exploreAction(Request $request)
    {
        if (!$this->setting('course.live_course_enabled')) {
            return $this->createMessageResponse('info', $this->get('translator')->trans('直播频道已关闭'));
        }

        return $this->render('course-set/live/explore.html.twig');
    }

    public function replayListAction()
    {
        $publishedCourseSetIds = $this->_findPublishedLiveCourseSetIds();

        $liveReplayList = $this->getTaskService()->searchTasks(
            [
                'endTime_LT' => time(),
                'type' => 'live',
                'copyId' => 0,
                'status' => 'published',
                'fromCourseSetIds' => $publishedCourseSetIds,
            ],
            ['startTime' => 'DESC'],
            0,
            10
        );

        return $this->render(
            'course-set/live/replay-list.html.twig',
            [
                'liveReplayList' => $liveReplayList,
            ]
        );
    }

    public function liveTabAction()
    {
        $currentLiveTasks = $this->getTaskService()->findCurrentLiveTasks();
        $dayTasks = $this->getTaskService()->searchTasks(
            [
                'type' => 'live',
                'status' => 'published',
                'startTime_GT' => time(),
            ],
            ['startTime' => 'ASC'],
            0,
            PHP_INT_MAX
        );

        $this->filterUnPublishTasks($currentLiveTasks, $dayTasks);
        $liveTabs['today']['current'] = $currentLiveTasks;
        $dateTabs = ['today'];
        $today = date('Y-m-d');

        foreach ($dayTasks as $key => $value) {
            $timeKey = date('Y-m-d', $value['startTime']);
            $shortTimeKey = date('m-d', $value['startTime']);
            if ($timeKey === $today) {
                $liveTabs['today']['future'][] = $value;
            } else {
                $liveTabs[$shortTimeKey]['future'][] = $value;
                $dateTabs[] = $shortTimeKey;
            }
        }
        $dateTabs = array_unique($dateTabs);
        list($dateTabs, $liveTabs) = $this->filterliveTabs($dateTabs, $liveTabs, 4);

        return $this->render(
            'course-set/live/tab.html.twig',
            [
                'liveTabs' => $liveTabs,
                'dateTabs' => $dateTabs,
            ]
        );
    }

    private function filterUnPublishTasks(&$currentLiveTasks, &$dayTasks)
    {
        $courseIds = array_merge(array_column($currentLiveTasks, 'courseId'), array_column($dayTasks, 'courseId'));
        $courseSetIds = array_merge(array_column($currentLiveTasks, 'fromCourseSetId'), array_column($dayTasks, 'fromCourseSetId'));
        $courses = $this->getCourseService()->findCoursesByIds($courseIds);
        $courseSets = $this->getCourseSetService()->findCourseSetsByIds($courseSetIds);

        foreach ($currentLiveTasks as $key => $currentLiveTask) {
            if ('published' !== $courses[$currentLiveTask['courseId']]['status']
                || 'published' !== $courseSets[$currentLiveTask['fromCourseSetId']]['status']) {
                unset($currentLiveTasks[$key]);
            }
        }

        foreach ($dayTasks as $key => $dayTask) {
            if ('published' !== $courses[$dayTask['courseId']]['status']
                || 'published' !== $courseSets[$dayTask['fromCourseSetId']]['status']) {
                unset($dayTasks[$key]);
            }
        }
    }

    private function filterliveTabs($dateTabs, $liveTabs, $num)
    {
        $dateTabs = array_slice($dateTabs, 0, $num);
        foreach ($liveTabs as $key => $value) {
            if (!in_array($key, $dateTabs)) {
                unset($liveTabs[$key]);
            }
        }

        return [$dateTabs, $liveTabs];
    }

    public function liveCourseSetsAction(Request $request)
    {
        $categoryId = $request->query->get('categoryId', '');
        $vipCategoryId = $request->query->get('vipCategoryId', '');
        $currentPage = $request->query->get('page', 1);

        $vipCourseSetIds = $this->_findVipCourseSetIds($vipCategoryId);
        $futureLiveCourseSets = $this->_findFutureLiveCourseSets($vipCourseSetIds, $categoryId);

        $paginator = new Paginator(
            $request,
            $this->getCourseSetService()->countCourseSets(
                [
                    'ids' => $vipCourseSetIds,
                    'type' => 'live',
                    'status' => 'published',
                    'categoryId' => $categoryId,
                ]
            ),
            10
        );
        $replayLiveCourseSets = [];
        if (count($futureLiveCourseSets) < $paginator->getPerPageCount()) {
            $futureLiveCourseSetIds = ArrayToolkit::column($futureLiveCourseSets, 'id');
            $replayLiveCourseSetIds = array_diff($vipCourseSetIds, $futureLiveCourseSetIds);
            $replayLiveCourseSets = $this->_findReplayLiveCourseSets(
                $currentPage,
                $replayLiveCourseSetIds,
                $categoryId
            );
        }

        $liveCourseSets = array_merge($futureLiveCourseSets, $replayLiveCourseSets);
        $liveCourseSets = ArrayToolkit::index($liveCourseSets, 'id');
        $liveCourseSets = $this->_fillLiveCourseSetAttribute($liveCourseSets);

        $levels = [];
        if ($this->isPluginInstalled('Vip')) {
            $levels = ArrayToolkit::index(
                $this->getLevelService()->searchLevels(['enabled' => 1], [], 0, 100),
                'id'
            );
        }

        return $this->render(
            'course-set/live/all-list.html.twig',
            [
                'liveCourseSets' => $liveCourseSets,
                'paginator' => $paginator,
                'request' => $request,
                'levels' => $levels,
            ]
        );
    }

    private function _fillLiveCourseSetAttribute($liveCourseSets)
    {
        if (empty($liveCourseSets)) {
            return [];
        }
        $liveCourseSetIds = array_keys($liveCourseSets);
        $courses = $this->getCourseService()->findCoursesByCourseSetIds($liveCourseSetIds);
        $courses = ArrayToolkit::index($courses, 'courseSetId');
        $ret = [];
        foreach ($liveCourseSetIds as $key => $courseSetId) {
            $ret[$courseSetId] = $liveCourseSets[$courseSetId];
            $ret[$courseSetId]['course'] = $courses[$courseSetId];
            $now = time();

            //正在直播的课时
            $tasks = $this->getTaskService()->searchTasks(
                ['fromCourseSetId' => $courseSetId, 'type' => 'live', 'startTime_LE' => $now, 'endTime_GT' => $now],
                ['startTime' => 'ASC'],
                0,
                1
            );
            if (empty($tasks)) {
                //第一个已经结束的课时课程
                $tasks = $this->getTaskService()->searchTasks(
                    ['fromCourseSetId' => $courseSetId, 'type' => 'live', 'endTime_LT' => $now],
                    ['startTime' => 'ASC'],
                    0,
                    1
                );
                //第一个未开始过的课时
                $advanceTasks = $this->getTaskService()->searchTasks(
                    ['fromCourseSetId' => $courseSetId, 'type' => 'live', 'endTime_GT' => $now],
                    ['startTime' => 'ASC'],
                    0,
                    1
                );
                if (!empty($advanceTasks)) {
                    $ret[$courseSetId]['advanceTime'] = $advanceTasks[0]['startTime'];
                }
            }

            if (empty($tasks)) {
                continue;
            }

            $ret[$courseSetId]['liveStartTime'] = $tasks[0]['startTime'];
            $ret[$courseSetId]['liveEndTime'] = $tasks[0]['endTime'];
            $ret[$courseSetId]['taskId'] = $tasks[0]['id'];
        }

        return $ret;
    }

    private function _findVipCourseSetIds($vipLevelId)
    {
        if (!$this->isPluginInstalled('Vip')) {
            return [];
        }

        $preLevelIds = ArrayToolkit::column($this->getLevelService()->findPrevEnabledLevels($vipLevelId), 'id');

        $vipCourseConditions = [
            'status' => 'published',
            'parentId' => 0,
            'display' => '1',
        ];

        if (!empty($vipLevelId)) {
            $preLevelIds = array_merge($preLevelIds, [$vipLevelId]);
            $vipCourseConditions['vipLevelIds'] = $preLevelIds;
        }

        $vipCourses = $this->getCourseService()->searchCourses(
            $vipCourseConditions,
            'latest',
            0,
            PHP_INT_MAX
        );
        $vipCourseSetIds = ArrayToolkit::column($vipCourses, 'courseSetId');

        return $vipCourseSetIds;
    }

    private function _findFutureLiveCourseSets($courseSetIds, $categoryId)
    {
        $futureLiveTasks = $this->getTaskService()->findFutureLiveTasks();
        $futureCourseSetIds = ArrayToolkit::column($futureLiveTasks, 'fromCourseSetId');
        $futureLiveCourseSetIds = array_intersect($futureCourseSetIds, $courseSetIds);
        if (empty($futureLiveCourseSetIds)) {
            $futureLiveCourseSetIds = [-1];
        }
        $condition = [
            'status' => 'published',
            'type' => 'live',
            'ids' => $futureLiveCourseSetIds,
            'categoryId' => $categoryId,
            'display' => '1',
        ];
        $futureLiveCourseSets = $this->getCourseSetService()->searchCourseSets(
            $condition,
            'latest',
            0,
            PHP_INT_MAX
        );

        return $futureLiveCourseSets;
    }

    private function _findReplayLiveCourseSets($currentPage, $replayLiveCourseSetIds, $categoryId)
    {
        $pageSize = 10;

        if (isset($conditions['ids'])) {
            $futureLiveCourseSetsCount = $this->getCourseSetService()->countCourseSets($conditions);
        } else {
            $futureLiveCourseSetsCount = 0;
        }

        $pages = $futureLiveCourseSetsCount <= $pageSize ? 1 : floor($futureLiveCourseSetsCount / $pageSize);

        if ($pages == $currentPage) {
            $start = 0;
            $limit = $pageSize - ($futureLiveCourseSetsCount % $pageSize);
        } else {
            $start = ($currentPage - 1) * $pageSize;
            $limit = $pageSize;
        }

        $conditions = [
            'ids' => $replayLiveCourseSetIds,
            'type' => 'live',
            'status' => 'published',
            'categoryId' => $categoryId,
        ];
        $replayLiveCourseSets = $this->getCourseSetService()->searchCourseSets(
            $conditions,
            ['createdTime' => 'DESC'],
            $start,
            $limit
        );

        return $replayLiveCourseSets;
    }

    private function _findPublishedLiveCourseSetIds()
    {
        $conditions = [
            'status' => 'published',
            'type' => 'live',
            'parentId' => 0,
            'display' => '1',
        ];
        $publishedCourseSets = $this->getCourseSetService()->searchCourseSets(
            $conditions,
            ['createdTime' => 'DESC'],
            0,
            PHP_INT_MAX
        );

        return ArrayToolkit::column($publishedCourseSets, 'id');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    /**
     * @return LevelService
     */
    protected function getLevelService()
    {
        return $this->createService('VipPlugin:Vip:LevelService');
    }
}
