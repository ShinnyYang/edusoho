<?php

use AppBundle\Common\ArrayToolkit;
use Biz\Activity\Dao\ActivityDao;
use Biz\Course\Dao\CourseDao;
use Biz\Task\Service\TaskService;
use Symfony\Component\Filesystem\Filesystem;
use Biz\Util\EdusohoLiveClient;

class EduSohoUpgrade extends AbstractUpdater
{
    public function __construct($biz)
    {
        parent::__construct($biz);
    }

    public function update($index = 0)
    {
        $this->getConnection()->beginTransaction();
        try {
            $result = $this->updateScheme((int)$index);
            $this->getConnection()->commit();
            if (!empty($result)) {
                return $result;
            } else {
                $this->logger('info', '执行升级脚本结束');
            }
        } catch (\Exception $e) {
            $this->getConnection()->rollback();
            $this->logger('error', $e->getTraceAsString());
            throw $e;
        }
        try {
            $dir = realpath($this->biz['kernel.root_dir'] . '/../web/install');
            $filesystem = new Filesystem();
            if (!empty($dir)) {
                $filesystem->remove($dir);
            }
        } catch (\Exception $e) {
            $this->logger('error', $e->getTraceAsString());
        }
        $developerSetting = $this->getSettingService()->get('developer', array());
        $developerSetting['debug'] = 0;
        $this->getSettingService()->set('developer', $developerSetting);
        $this->getSettingService()->set('crontab_next_executed_time', time());
    }

    private function updateScheme($index)
    {
        $definedFuncNames = array(
            'registerCallbackUrl',
            'registerSyncTask',
        );
        $funcNames = array();
        foreach ($definedFuncNames as $key => $funcName) {
            $funcNames[$key + 1] = $funcName;
        }
        if (0 == $index) {
            $this->logger('info', '开始执行升级脚本');

            return array(
                'index' => $this->generateIndex(1, 1),
                'message' => '升级数据...',
                'progress' => 0,
            );
        }
        list($step, $page) = $this->getStepAndPage($index);
        $method = $funcNames[$step];
        $page = $this->$method($page);
        if (1 == $page) {
            ++$step;
        }
        if ($step <= count($funcNames)) {
            return array(
                'index' => $this->generateIndex($step, $page),
                'message' => '升级数据...',
                'progress' => 0,
            );
        }
    }

    public function registerCallbackUrl()
    {
        try {
            $site = $this->getSettingService()->get('site', []);
            if (empty($site['url'])) {
                return 1;
            }
            $client = new EdusohoLiveClient();
            $client->uploadCallbackUrl(rtrim($site['url'], '/').'/callback/live/handle');
            $this->logger('info', '修改直播回调');
        } catch (\RuntimeException $e) {
        }

        return 1;
    }

    public function registerSyncTask($page)
    {
        if($page > 1) {
            $logPage = $page / 1000;
            $coursePage = $page % 1000;
        }else {
            $logPage = 1;
            $coursePage = 1;
        }
        $jobLogs = $this->getJobLogDao()->search(['name'=>'course_task_create_sync_job_', 'status'=>'error'],['id'=>'asc'], ($logPage-1) * 1, 1);
        $taskIds = ArrayToolkit::column(ArrayToolkit::column($jobLogs,'args'), 'taskId');
        $tasks = $this->getTaskService()->findTasksByIds($taskIds);
        $courseIds = ArrayToolkit::column($tasks, 'courseId');

        if(empty($courseIds)) {
            return 1;
        }
        foreach ($courseIds as $courseId) {
            $copiedCourses = $this->getCourseDao()->findCoursesByParentIdAndLocked($courseId, 1);
            foreach ($copiedCourses as $index=>$copiedCourse) {
                if($coursePage - 1 == $index) {
                    $result = $this->getTaskService()->syncClassroomCourseTasks($copiedCourse['id'], true);
                    $this->logger('info', json_encode($result));
                }
            }
            if(!empty($copiedCourses) && $coursePage == $index) {
                $coursePage = 1;
                $logPage++;
            }
        }

        return (int)($logPage.$coursePage);
    }


    protected function generateIndex($step, $page)
    {
        return $step * 1000000 + $page;
    }

    protected function getStepAndPage($index)
    {
        $step = intval($index / 1000000);
        $page = $index % 1000000;

        return array($step, $page);
    }

    protected function isFieldExist($table, $filedName)
    {
        $sql = "DESCRIBE `{$table}` `{$filedName}`;";
        $result = $this->getConnection()->fetchAssoc($sql);

        return empty($result) ? false : true;
    }

    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    /**
     * @return TaskService
     */
    public function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    protected function getJobLogDao()
    {
        return $this->createDao('Scheduler:JobLogDao');
    }

    /**
     * @return CourseDao
     */
    protected function getCourseDao()
    {
        return $this->createDao('Course:CourseDao');
    }
}

abstract class AbstractUpdater
{
    protected $biz;

    public function __construct($biz)
    {
        $this->biz = $biz;
    }

    public function getConnection()
    {
        return $this->biz['db'];
    }

    protected function createService($name)
    {
        return $this->biz->service($name);
    }

    protected function createDao($name)
    {
        return $this->biz->dao($name);
    }

    abstract public function update();

    protected function logger($level, $message)
    {
        $version = \AppBundle\System::VERSION;
        $data = date('Y-m-d H:i:s') . " [{$level}] {$version} " . $message . PHP_EOL;
        if (!file_exists($this->getLoggerFile())) {
            touch($this->getLoggerFile());
        }
        file_put_contents($this->getLoggerFile(), $data, FILE_APPEND);
    }

    private function getLoggerFile()
    {
        return $this->biz['kernel.root_dir'] . '/../app/logs/upgrade.log';
    }

    protected function getAppLogDao()
    {
        return $this->createDao('CloudPlatform:CloudAppLogDao');
    }
}
