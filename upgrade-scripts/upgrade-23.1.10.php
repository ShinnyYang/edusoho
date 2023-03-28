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
            'refreshRoles',
            'createTableSmsRequestLog',
            'createTableAntiFraudRemind'
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

    public function refreshRoles()
    {
        $this->getRoleService()->refreshRoles();

        return 1;
    }

    public function createTableSmsRequestLog()
    {
        if (!$this->isTableExist('sms_request_log')) {
            $this->getConnection()->exec("
        CREATE TABLE `sms_request_log` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
            `fingerprint` varchar(32) NOT NULL COMMENT '行为指纹',
            `coordinate` varchar(16) NOT NULL COMMENT '坐标',
            `ip` varchar(32) NOT NULL COMMENT 'ip',
            `mobile` varchar(11)  NOT NULL COMMENT '手机号',
            `userAgent` varchar(255) NOT NULL DEFAULT '',
            `isIllegal`  tinyint(1)  NOT NULL COMMENT '是非法记录',
            `disableType`  varchar(16)  NOT NULL default 'none' COMMENT '禁用类型',
            `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
            `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
            PRIMARY KEY (`id`),
            KEY `mobile` (mobile),
            KEY `ip` (ip),
            KEY `createdTime` (createdTime)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='短信请求日志表';
            ALTER TABLE `behavior_verification_black_ip` rename to `sms_black_list`;
            DROP TABLE `behavior_verification_coordinate`;
        ");
        }
        return 1;
    }

    public function createTableAntiFraudRemind()
    {
        if (!$this->isTableExist('anti_fraud_remind')) {
            $this->getConnection()->exec("
        CREATE TABLE `anti_fraud_remind` (
             `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
            `userId` int unsigned DEFAULT '0' COMMENT '用户id',
            `lastRemindTime` int unsigned NOT NULL DEFAULT '0' COMMENT '上次提醒时间',
            `createdTime` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
            `updatedTime` int unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='防诈骗弹窗提醒频率表';
        ");
        }
        return 1;
    }
    protected function installPluginAssets($plugins)
    {
        $rootDir = realpath($this->biz['kernel.root_dir'].'/../');
        foreach ($plugins as $plugin) {
            $pluginApp = $this->getAppService()->getAppByCode($plugin);
            if (empty($pluginApp)) {
                continue;
            }
            $originDir = "{$rootDir}/plugins/{$plugin}Plugin/Resources/public";
            $targetDir = "{$rootDir}/web/bundles/".strtolower($plugin).'plugin';
            $filesystem = new Filesystem();
            if ($filesystem->exists($targetDir)) {
                $filesystem->remove($targetDir);
            }
            if ($filesystem->exists($originDir)) {
                $filesystem->mirror($originDir, $targetDir, null, ['override' => true, 'delete' => true]);
            }
            $originDir = "{$rootDir}/plugins/{$plugin}Plugin/Resources/static-dist/".strtolower($plugin).'plugin/';
            if (!is_dir($originDir)) {
                return false;
            }
            $targetDir = "{$rootDir}/web/static-dist/".strtolower($plugin).'plugin/';
            $filesystem = new Filesystem();
            $filesystem->mirror($originDir, $targetDir, null, ['override' => true, 'delete' => true]);
        }
    }

    protected function deleteCache()
    {
        $cachePath = $this->biz['cache_directory'];
        $filesystem = new Filesystem();
        $filesystem->remove($cachePath);
        clearstatcache(true);
        $this->logger('info', '删除缓存');

        return 1;
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

    protected function isTableExist($table)
    {
        $sql = "SHOW TABLES LIKE '{$table}'";
        $result = $this->biz['db']->fetchAssoc($sql);
        return empty($result) ? false : true;
    }

    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    /**
     * @return \Biz\CloudPlatform\Service\AppService
     */
    protected function getAppService()
    {
        return $this->createService('CloudPlatform:AppService');
    }

    /**
     * @return \Biz\Role\Service\RoleService
     */
    protected function getRoleService()
    {
        return $this->createService('Role:RoleService');
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
