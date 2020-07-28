<?php

use Symfony\Component\Filesystem\Filesystem;
use AppBundle\Common\ArrayToolkit;

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
            $result = $this->updateScheme($index);

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
            $dir = realpath($this->biz['kernel.root_dir'].'/../web/install');
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
            'addBizItemCategoryColumn',
            'updateBizItemCategory',
            'addBizItemBankColumn',
            'updateBizItemBankColumn',
            'addBizAnswerSceneColumn',
            'createItemBankAssessmentExerciseTable',
            'createItemBankAssessmentExerciseRecordTable',
            'createItemBankChapterExerciseRecordTable',
            'createItemBankExerciseTable',
            'createItemBankExerciseMemberTable',
            'createItemBankExerciseMemberOperationRecordTable',
            'createItemBankExerciseModuleTable',
            'createItemBankExerciseQuestionRecordTable',
        );

        $funcNames = array();
        foreach ($definedFuncNames as $key => $funcName) {
            $funcNames[$key + 1] = $funcName;
        }

        if (0 == $index) {
            $this->logger('info', '开始执行升级脚本');
            $this->deleteCache();

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

    public function addBizItemCategoryColumn()
    {
        $this->logger('info', 'biz_item_category创建item_num, question_num字段');

        if (!$this->isFieldExist('biz_item_category', 'item_num')) {
            $this->getConnection()->exec("ALTER TABLE `biz_item_category` ADD `item_num` INT(10) unsigned NOT NULL DEFAULT '0' COMMENT '题目总数' AFTER `bank_id`;");
        }

        if (!$this->isFieldExist('biz_item_category', 'question_num')) {
            $this->getConnection()->exec("ALTER TABLE `biz_item_category` ADD `question_num` INT(10) unsigned NOT NULL DEFAULT '0' COMMENT '问题总数' AFTER `item_num`;");
        }

        return 1;
    }

    public function updateBizItemCategory()
    {
        $this->logger('info', '处理biz_item_category item_num, question_num字段数据');
        
        $this->getConnection()->exec("
            UPDATE `biz_item_category` category
                INNER JOIN (
                    SELECT category_id, SUM(question_num) AS question_num, COUNT(*) AS item_num
                    FROM `biz_item` item
                    WHERE category_id > 0
                    GROUP BY item.category_id
                ) item
                ON category.id = item.category_id
            SET category.question_num = item.question_num, category.item_num = item.item_num;
        ");

        return 1;
    }

    public function addBizItemBankColumn()
    {
        $this->logger('info', 'biz_item_bank创建question_num字段');

        if (!$this->isFieldExist('biz_item_bank', 'question_num')) {
            $this->getConnection()->exec("ALTER TABLE `biz_item_bank` ADD `question_num` INT(10) unsigned NOT NULL DEFAULT '0' COMMENT '问题总数' AFTER `item_num`;");
        }

        return 1;
    }

    public function updateBizItemBankColumn()
    {
        $this->logger('info', 'biz_item_bank question_num字段数据');

        $this->getConnection()->exec("
            UPDATE `biz_item_bank` bank
                INNER JOIN (
                    SELECT bank_id, SUM(question_num) AS question_num
                    FROM `biz_item` item
                    WHERE bank_id > 0
                    GROUP BY item.bank_id
                ) item
                ON bank.id = item.bank_id
            SET bank.question_num = item.question_num;
        ");

        return 1;
    }

    public function addBizAnswerSceneColumn()
    {
        $this->logger('info', 'biz_answer_scene创建doing_look_analysis字段');

        if (!$this->isFieldExist('biz_answer_scene', 'doing_look_analysis')) {
            $this->getConnection()->exec("ALTER TABLE `biz_answer_scene` ADD `doing_look_analysis` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '支持做题中查看解析' AFTER `start_time`;");
        }

        return 1;
    }

    public function createItemBankAssessmentExerciseTable()
    {
        $this->logger('info', '创建试卷练习设置表');

        if (!$this->isTableExist('item_bank_assessment_exercise')) {
            $this->getConnection()->exec("
                CREATE TABLE `item_bank_assessment_exercise` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `exerciseId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '题库练习id',
                    `moduleId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '模块id',
                    `assessmentId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '试卷id',
                    `createdTime` int(11) unsigned NOT NULL DEFAULT '0',
                    `updatedTime` int(11) unsigned NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`),
                    KEY `moduleId` (`moduleId`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='试卷练习设置';
            ");
        }

        return 1;
    }

    public function createItemBankAssessmentExerciseRecordTable()
    {
        $this->logger('info', '创建试卷练习记录表');

        if (!$this->isTableExist('item_bank_assessment_exercise_record')) {
            $this->getConnection()->exec("
                CREATE TABLE `item_bank_assessment_exercise_record` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `exerciseId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '题库练习id',
                    `assessmentExerciseId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '试卷练习任务id',
                    `moduleId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '模块id',
                    `assessmentId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '试卷id',
                    `userId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
                    `answerRecordId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '答题记录id',
                    `status` enum('doing','paused','reviewing','finished') NOT NULL DEFAULT 'doing' COMMENT '答题状态',
                    `createdTime` int(11) unsigned NOT NULL DEFAULT '0',
                    `updatedTime` int(11) unsigned NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`),
                    KEY `answerRecordId` (`answerRecordId`),
                    KEY `moduleId` (`moduleId`),
                    KEY `userId` (`userId`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='试卷练习记录表';
            ");
        }

        return 1;
    }

    public function createItemBankChapterExerciseRecordTable()
    {
        $this->logger('info', '创建题库章节练习记表');

        if (!$this->isTableExist('item_bank_chapter_exercise_record')) {
            $this->getConnection()->exec("
                CREATE TABLE `item_bank_chapter_exercise_record` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `moduleId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '模块id',
                    `exerciseId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '题库练习id',
                    `itemCategoryId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '题目分类id',
                    `userId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
                    `status` enum('doing','paused','reviewing','finished') NOT NULL DEFAULT 'doing' COMMENT '答题状态',
                    `answerRecordId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '答题记录id',
                    `questionNum` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '问题总数',
                    `doneQuestionNum` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '回答题目数',
                    `rightQuestionNum` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '答题问题数',
                    `rightRate` float(10,1) NOT NULL DEFAULT '0.0' COMMENT '正确率',
                    `createdTime` int(11) unsigned NOT NULL DEFAULT '0',
                    `updatedTime` int(11) unsigned NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`),
                    KEY `moduleId` (`moduleId`),
                    KEY `userId` (`userId`),
                    KEY `answerRecordId` (`answerRecordId`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='题库章节练习记录';
            ");
        }

        return 1;
    }

    public function createItemBankExerciseTable()
    {
        $this->logger('info', '创建题库练习表');

        if (!$this->isTableExist('item_bank_exercise')) {
            $this->getConnection()->exec("
                CREATE TABLE `item_bank_exercise` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `seq` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '序号',
                    `title` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
                    `status` varchar(32) NOT NULL DEFAULT 'draft' COMMENT '状态  draft, published, closed',
                    `chapterEnable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '章节练习是否开启',
                    `assessmentEnable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '试卷练习是否开启',
                    `questionBankId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '资源题库id',
                    `categoryId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '题库分类id',
                    `cover` varchar(1024) NOT NULL DEFAULT '' COMMENT '封面图',
                    `studentNum` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '学员总数',
                    `teacherIds` varchar(1024) NOT NULL DEFAULT '' COMMENT '教师ID列表',
                    `joinEnable` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否允许加入',
                    `expiryMode` varchar(32) NOT NULL DEFAULT 'forever' COMMENT '过期方式 days,date,end_date,forever',
                    `expiryDays` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '过期天数',
                    `expiryStartDate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '有效期开始时间',
                    `expiryEndDate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '有效期结束时间',
                    `isFree` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否免费1表示免费',
                    `income` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '总收入',
                    `price` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '售价',
                    `originPrice` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '原价',
                    `maxRate` tinyint(3) unsigned NOT NULL DEFAULT '100' COMMENT '最大抵扣百分比',
                    `ratingNum` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
                    `rating` float unsigned NOT NULL DEFAULT '0' COMMENT '评分',
                    `recommended` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐',
                    `recommendedSeq` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '推荐序号',
                    `recommendedTime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '推荐时间',
                    `creator` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建者',
                    `createdTime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
                    `updatedTime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后修改时间',
                    PRIMARY KEY (`id`),
                    KEY `questionBankId` (`questionBankId`),
                    KEY `categoryId` (`categoryId`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='题库练习表';
            ");
        }

        return 1;
    }

    public function createItemBankExerciseMemberTable()
    {
        $this->logger('info', '创建题库练习成员表');

        if (!$this->isTableExist('item_bank_exercise_member')) {
            $this->getConnection()->exec("
                CREATE TABLE `item_bank_exercise_member` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `exerciseId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '题库练习id',
                    `questionBankId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '资源题库id',
                    `userId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户Id',
                    `orderId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单id',
                    `deadline` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '学习最后期限',
                    `doneQuestionNum` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '相对当前题库的已做问题总数',
                    `rightQuestionNum` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '相对当前题库的做对问题总数',
                    `masteryRate` float(10,1) NOT NULL DEFAULT '0.0' COMMENT '相对当前题库的掌握度',
                    `completionRate` float(10,1) NOT NULL DEFAULT '0.0' COMMENT '相对当前题库的完成率',
                    `role` enum('student','teacher') NOT NULL DEFAULT 'student' COMMENT '成员角色',
                    `locked` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '学员是否锁定',
                    `deadlineNotified` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '有效期通知',
                    `remark` varchar(255) NOT NULL COMMENT '备注',
                    `createdTime` int(11) unsigned NOT NULL DEFAULT '0',
                    `updatedTime` int(11) unsigned NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`),
                    KEY `exerciseId` (`exerciseId`),
                    KEY `userId` (`userId`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='题库练习成员';
            ");
        }

        return 1;
    }

    public function createItemBankExerciseMemberOperationRecordTable()
    {
        $this->logger('info', '创建题库练习成员操作记录表');

        if (!$this->isTableExist('item_bank_exercise_member_operation_record')) {
            $this->getConnection()->exec("
                CREATE TABLE `item_bank_exercise_member_operation_record` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `title` varchar(1024) NOT NULL DEFAULT '' COMMENT '题库练习名称',
                    `memberId` int(10) unsigned NOT NULL COMMENT '成员ID',
                    `memberType` varchar(32) NOT NULL DEFAULT 'student' COMMENT '成员身份',
                    `exerciseId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '题库练习ID',
                    `operateType` enum('join','exit') NOT NULL DEFAULT 'join' COMMENT '操作类型（join=加入, exit=退出）',
                    `operateTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作时间',
                    `operatorId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作用户ID',
                    `userId` int(11) NOT NULL DEFAULT '0' COMMENT '用户Id',
                    `orderId` int(11) NOT NULL DEFAULT '0' COMMENT '订单ID',
                    `refundId` int(11) NOT NULL DEFAULT '0' COMMENT '退款ID',
                    `reason` varchar(256) NOT NULL DEFAULT '' COMMENT '加入理由或退出理由',
                    `reasonType` varchar(255) NOT NULL DEFAULT '' COMMENT '用户退出或加入的类型',
                    `createdTime` int(10) unsigned NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`),
                    KEY `exerciseId` (`exerciseId`),
                    KEY `userId` (`userId`),
                    KEY `operateType` (`operateType`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='题库练习成员加入记录表';
            ");
        }

        return 1;
    }

    public function createItemBankExerciseModuleTable()
    {
        $this->logger('info', '练习模块表');

        if (!$this->isTableExist('item_bank_exercise_module')) {
            $this->getConnection()->exec("
                CREATE TABLE `item_bank_exercise_module` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `seq` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '序号',
                    `exerciseId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '题库练习id',
                    `answerSceneId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '场次id',
                    `title` varchar(255) NOT NULL DEFAULT '' COMMENT '模块标题',
                    `type` enum('chapter','assessment') NOT NULL DEFAULT 'chapter' COMMENT '模块类型',
                    `createdTime` int(11) unsigned NOT NULL DEFAULT '0',
                    `updatedTime` int(11) unsigned NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`),
                    KEY `exerciseId` (`exerciseId`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='练习模块表';
            ");
        }

        return 1;
    }

    public function createItemBankExerciseQuestionRecordTable()
    {
        $this->logger('info', '创建已做题目表');

        if (!$this->isTableExist('item_bank_exercise_question_record')) {
            $this->getConnection()->exec("
                CREATE TABLE `item_bank_exercise_question_record` (
                    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                    `exerciseId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '题库练习id',
                    `answerRecordId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '答题记录id',
                    `moduleType` enum('chapter','assessment') NOT NULL DEFAULT 'chapter' COMMENT '模块类型',
                    `itemId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '题目id',
                    `questionId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '问题id',
                    `userId` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
                    `status` enum('right','wrong') NOT NULL DEFAULT 'wrong' COMMENT '状态',
                    `createdTime` int(11) unsigned NOT NULL DEFAULT '0',
                    `updatedTime` int(11) unsigned NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`),
                    KEY `exerciseId` (`exerciseId`),
                    KEY `userId` (`userId`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='已做题目表';
            ");
        }

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
        $result = $this->getConnection()->fetchAssoc($sql);

        return empty($result) ? false : true;
    }

    protected function isIndexExist($table, $indexName)
    {
        $sql = "show index from `{$table}` where key_name='{$indexName}';";
        $result = $this->getConnection()->fetchAssoc($sql);
        return empty($result) ? false : true;
    }

    protected function createIndex($table, $index, $column)
    {
        if (!$this->isIndexExist($table, $column, $index)) {
            $this->getConnection()->exec("ALTER TABLE {$table} ADD INDEX {$index} ({$column})");
        }
    }

    protected function isJobExist($code)
    {
        $sql = "select * from biz_scheduler_job where name='{$code}'";
        $result = $this->getConnection()->fetchAssoc($sql);

        return empty($result) ? false : true;
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

    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
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
        $data = date('Y-m-d H:i:s')." [{$level}] {$version} ".$message.PHP_EOL;
        if (!file_exists($this->getLoggerFile())) {
            touch($this->getLoggerFile());
        }
        file_put_contents($this->getLoggerFile(), $data, FILE_APPEND);
    }

    private function getLoggerFile()
    {
        return $this->biz['kernel.root_dir'].'/../app/logs/upgrade.log';
    }

    /**
     * @return \Biz\DiscoveryColumn\Service\DiscoveryColumnService
     */
    protected function getDiscoveryColumnService()
    {
        return $this->createService('DiscoveryColumn:DiscoveryColumnService');
    }

    /**
     * @return \Biz\Taxonomy\Service\CategoryService
     */
    protected function getCategoryService()
    {
        return $this->createService('Taxonomy:CategoryService');
    }

    /**
     * @return \Biz\System\Service\H5SettingService
     */
    protected function getH5SettingService()
    {
        return $this->createService('System:H5SettingService');
    }

    /**
     * @return \Biz\Course\Service\CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }
}
