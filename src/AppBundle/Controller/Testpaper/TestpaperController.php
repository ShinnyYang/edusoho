<?php

namespace AppBundle\Controller\Testpaper;

use AppBundle\Controller\BaseController;
use Biz\Activity\Service\ActivityService;
use Biz\Activity\Service\TestpaperActivityService;
use Biz\Activity\Type\Testpaper;
use Biz\Common\CommonException;
use Biz\Course\CourseException;
use Biz\Course\Service\CourseService;
use Biz\System\Service\SettingService;
use Biz\Task\Service\TaskService;
use Biz\User\Service\UserService;
use Biz\User\UserException;
use Codeages\Biz\ItemBank\Answer\Service\AnswerRecordService;
use Codeages\Biz\ItemBank\Answer\Service\AnswerReportService;
use Codeages\Biz\ItemBank\Answer\Service\AnswerSceneService;
use Codeages\Biz\ItemBank\Answer\Service\AnswerService;
use Codeages\Biz\ItemBank\Assessment\Service\AssessmentService;
use Symfony\Component\HttpFoundation\Request;

class TestpaperController extends BaseController
{
    //由于学习引擎改造，这里的 lessonId 等于 activityId
    public function doTestpaperAction(Request $request, $testId, $lessonId)
    {
        $activity = $this->getActivityService()->getActivity($lessonId);
        $testpaperActivity = $this->getTestpaperActivityService()->getActivity($activity['mediaId']);
        $task = $this->getTaskService()->getTaskByCourseIdAndActivityId($activity['fromCourseId'], $activity['id']);

        $canTakeCourse = $this->getCourseService()->canTakeCourse($activity['fromCourseId']);
        if (!$canTakeCourse) {
            $this->createNewException(CourseException::FORBIDDEN_TAKE_COURSE());
        }

        $user = $this->getCurrentUser();
        $latestAnswerRecord = $this->getAnswerRecordService()->getLatestAnswerRecordByAnswerSceneIdAndUserId($testpaperActivity['answerSceneId'], $user['id']);
        if (empty($latestAnswerRecord) || AnswerService::ANSWER_RECORD_STATUS_FINISHED == $latestAnswerRecord['status']) {
            $latestAnswerRecord = $this->getAnswerService()->startAnswer($testpaperActivity['answerSceneId'], $testpaperActivity['mediaId'], $user['id']);
        }

        return $this->forward('AppBundle:AnswerEngine/AnswerEngine:do', [
            'answerRecordId' => $latestAnswerRecord['id'],
            'submitGotoUrl' => $this->generateUrl('course_task_activity_show', ['courseId' => $activity['fromCourseId'], 'id' => $task['id']]),
            'saveGotoUrl' => $this->generateUrl('my_course_show', ['id' => $activity['fromCourseId']]),
        ]);
    }

    public function showResultAction(Request $request, $answerRecordId, $type = 'default')
    {
        if (!$this->canLookAnswerRecord($answerRecordId)) {
            $this->createNewException(CommonException::FORBIDDEN_DRAG_CAPTCHA_ERROR());
        }

        $answerRecord = $this->getAnswerRecordService()->get($answerRecordId);
        $answerReport = $this->getAnswerReportService()->get($answerRecord['answer_report_id']);
        $answerScene = $this->getAnswerSceneService()->get($answerRecord['answer_scene_id']);
        $assessment = $this->getAssessmentService()->getAssessment($answerRecord['assessment_id']);

        if ('my' == $request->query->get('action', '')) {
            $task = $this->getTaskByAnswerSceneId($answerRecord['answer_scene_id']);
            $restartUrl = $this->generateUrl('course_task_show', ['id' => $task['id'], 'courseId' => $task['courseId']]);
        } elseif ('' == $request->query->get('action', '')) {
            $task = $this->getTaskByAnswerSceneId($answerRecord['answer_scene_id']);
            $restartUrl = $this->generateUrl('course_task_activity_show', ['courseId' => $task['courseId'], 'id' => $task['id'], 'doAgain' => true]);
        } else {
            $restartUrl = '';
        }

        return $this->render('testpaper/result.html.twig', [
            'passedStatus' => $answerReport['score'] >= $answerScene['pass_score'],
            'answerReport' => $answerReport,
            'answerRecord' => $answerRecord,
            'assessment' => $assessment,
            'restartUrl' => $restartUrl,
            'answerShow' => $this->getAnswerShow($answerRecord['answer_scene_id'], $answerRecord['status'], $answerScene['pass_score'], $answerReport['score']),
            'type' => $type,
        ]);
    }

    protected function getAnswerShow($answerSceneId, $answerRecordStatus, $passScore, $score)
    {
        $questionSetting = $this->getSettingService()->get('questions', []);
        $answerShowMode = empty($questionSetting['testpaper_answers_show_mode']) ? 'submitted' : $questionSetting['testpaper_answers_show_mode'];
        switch ($answerShowMode) {
            case 'hide':
                return 'none';
            case 'reviewed':
                if ($this->getAnswerRecordService()->count(['answer_scene_id' => $answerSceneId, 'statusNeq' => 'finished'])) {
                    return 'none';
                } else {
                    return 'show';
                }
                // no break
            case 'submitted':
                $testpaperActivity = $this->getTestpaperActivityService()->getActivityByAnswerSceneId($answerSceneId);
                if (0 == $testpaperActivity['doTimes'] && Testpaper::ANSWER_MODE_PASSED == $testpaperActivity['answerMode']) {
                    if ('finished' === $answerRecordStatus && $score >= $passScore) {
                        return 'show';
                    } else {
                        return 'none';
                    }
                }

                return 'show';
            default:
                return 'show';
                break;
        }
    }

    protected function getTaskByAnswerSceneId($answerSceneId)
    {
        $testpaperActivity = $this->getTestpaperActivityService()->getActivityByAnswerSceneId($answerSceneId);
        $activity = $this->getActivityService()->getByMediaIdAndMediaType($testpaperActivity['id'], 'testpaper');

        return $this->getTaskService()->getTaskByCourseIdAndActivityId($activity['fromCourseId'], $activity['id']);
    }

    protected function getActivityIdByAnswerSceneId($answerSceneId)
    {
        $testpaperActivity = $this->getTestpaperActivityService()->getActivityByAnswerSceneId($answerSceneId);

        return $this->getActivityService()->getByMediaIdAndMediaType($testpaperActivity['id'], 'testpaper')['id'];
    }

    protected function canLookAnswerRecord($answerRecordId)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            $this->createNewException(UserException::UN_LOGIN());
        }

        $answerRecord = $this->getAnswerRecordService()->get($answerRecordId);

        if (!$answerRecord) {
            $this->createNewException(CommonException::ERROR_PARAMETER());
        }

        if ('doing' === $answerRecord['status'] && ($answerRecord['user_id'] != $user['id'])) {
            $this->createNewException(CommonException::FORBIDDEN_DRAG_CAPTCHA_ERROR());
        }

        if ($user->isAdmin()) {
            return true;
        }

        $testpaperActivity = $this->getTestpaperActivityService()->getActivityByAnswerSceneId($answerRecord['answer_scene_id']);
        $activity = $this->getActivityService()->getByMediaIdAndMediaType($testpaperActivity['id'], 'testpaper');

        $course = $this->getCourseService()->getCourse($activity['fromCourseId']);
        $member = $this->getCourseMemberService()->getCourseMember($course['id'], $user['id']);

        if (in_array($member['role'], ['teacher', 'assistant'])) {
            return true;
        }

        if ($answerRecord['user_id'] == $user['id']) {
            return true;
        }

        if ($course['parentId'] > 0) {
            $classroom = $this->getClassroomService()->getClassroomByCourseId($course['id']);
            $member = $this->getClassroomService()->getClassroomMember($classroom['id'], $user['id']);

            if ($member && array_intersect($member['role'], ['assistant', 'teacher', 'headTeacher'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    /**
     * @return TestpaperActivityService
     */
    protected function getTestpaperActivityService()
    {
        return $this->createService('Activity:TestpaperActivityService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    /**
     * @return AssessmentService
     */
    protected function getAssessmentService()
    {
        return $this->createService('ItemBank:Assessment:AssessmentService');
    }

    /**
     * @return AnswerRecordService
     */
    protected function getAnswerRecordService()
    {
        return $this->createService('ItemBank:Answer:AnswerRecordService');
    }

    /**
     * @return AnswerReportService
     */
    protected function getAnswerReportService()
    {
        return $this->createService('ItemBank:Answer:AnswerReportService');
    }

    /**
     * @return AnswerSceneService
     */
    protected function getAnswerSceneService()
    {
        return $this->createService('ItemBank:Answer:AnswerSceneService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    protected function getClassroomService()
    {
        return $this->createService('Classroom:ClassroomService');
    }

    /**
     * @return AnswerService
     */
    protected function getAnswerService()
    {
        return $this->createService('ItemBank:Answer:AnswerService');
    }
}
