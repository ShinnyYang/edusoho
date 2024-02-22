<?php

namespace AppBundle\Common\Tests;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\ReflectionUtils;
use AppBundle\Common\SystemInitializer;
use Biz\BaseTestCase;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;

class SystemInitializerTest extends BaseTestCase
{
    public function testInit()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);
        $initializer->init();
    }

    public function testInitPages()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);
        ReflectionUtils::invokeMethod($initializer, '_initPages', []);
        $result = $this->getContentService()->searchContents([], ['id' => 'ASC'], 0, \PHP_INT_MAX);

        $this->assertArrayEquals([
            'title' => '关于我们',
            'type' => 'page',
            'alias' => 'aboutus',
            'body' => '',
            'template' => 'default',
            'status' => 'published',
        ], ArrayToolkit::parts($result[0], ['title', 'type', 'alias', 'body', 'template', 'status']));

        $this->assertArrayEquals([
            'title' => '常见问题',
            'type' => 'page',
            'alias' => 'questions',
            'body' => '',
            'template' => 'default',
            'status' => 'published',
        ], ArrayToolkit::parts($result[1], ['title', 'type', 'alias', 'body', 'template', 'status']));
    }

    public function testInitCoin()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);
        ReflectionUtils::invokeMethod($initializer, '_initCoinSetting', []);
        $result = $this->getSettingService()->get('coin');
        $default = [
            'coin_enabled' => 0,
            'cash_model' => 'none',
            'cash_rate' => 1,
            'coin_name' => '虚拟币',
            'coin_content' => '',
            'coin_picture' => '',
            'coin_picture_50_50' => '',
            'coin_picture_30_30' => '',
            'coin_picture_20_20' => '',
            'coin_picture_10_10' => '',
            'charge_coin_enabled' => '',
        ];

        $this->assertArrayEquals($default, $result);
    }

    public function testInitNavigations()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);
        ReflectionUtils::invokeMethod($initializer, '_initNavigations', []);
        $result = $this->getNavigationService()->searchNavigations([], [], 0, \PHP_INT_MAX);

        $this->assertArrayEquals([
            'name' => '师资力量',
            'url' => 'teacher',
            'sequence' => 1,
            'isNewWin' => 0,
            'isOpen' => 1,
            'type' => 'top',
        ], ArrayToolkit::parts($result[0], ['name', 'url', 'sequence', 'isNewWin', 'isOpen', 'type']));

        $this->assertArrayEquals([
            'name' => '常见问题',
            'url' => 'page/questions',
            'sequence' => 2,
            'isNewWin' => 0,
            'isOpen' => 1,
            'type' => 'top',
        ], ArrayToolkit::parts($result[1], ['name', 'url', 'sequence', 'isNewWin', 'isOpen', 'type']));

        $this->assertArrayEquals([
            'name' => '关于我们',
            'url' => 'page/aboutus',
            'sequence' => 3,
            'isNewWin' => 0,
            'isOpen' => 1,
            'type' => 'top',
        ], ArrayToolkit::parts($result[2], ['name', 'url', 'sequence', 'isNewWin', 'isOpen', 'type']));
    }

    public function testInitThemes()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);
        ReflectionUtils::invokeMethod($initializer, '_initThemesSetting', []);

        $result = $this->getSettingService()->get('theme');
        $default = ['uri' => 'jianmo'];
        $this->assertArrayEquals($default, $result);
    }

    public function testInitBlocks()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);
        ReflectionUtils::invokeMethod($initializer, '_initBlocks', []);

        $result = $this->getBlockService()->searchBlockTemplates([], [], 0, \PHP_INT_MAX);
        $result = ArrayToolkit::column($result, 'code');

        $this->assertArrayEquals(
            [
                'live_top_banner',
                'default:home_top_banner',
                'autumn:home_top_banner',
                'jianmo:home_top_banner',
                'jianmo:middle_banner',
                'jianmo:advertisement_banner',
                'jianmo:bottom_info',
            ],
            $result
        );

        $result = $this->getBlockDao()->search([], [], 0, \PHP_INT_MAX);
        $this->assertTrue(empty($result));
    }

    public function testInitJob()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);
        ReflectionUtils::invokeMethod($initializer, '_initJob', []);

        $result = $this->getSchedulerService()->searchJobs([], [], 0, \PHP_INT_MAX);

        $this->assertEquals(26, count($result));

        $this->assertArrayEquals([
            'Order_FinishSuccessOrdersJob',
            'Order_CloseOrdersJob',
            'DeleteExpiredTokenJob',
            'SessionGcJob',
            'OnlineGcJob',
            'Scheduler_MarkExecutingTimeoutJob',
            'RefreshLearningProgressJob',
            'UpdateInviteRecordOrderInfoJob',
            'Xapi_PushStatementsJob',
            'Xapi_AddActivityWatchToStatementJob',
            'Xapi_ArchiveStatementJob',
            'Xapi_ConvertStatementsJob',
            'SyncUserTotalLearnStatisticsJob',
            'SyncUserLearnDailyPastLearnStatisticsJob',
            'DeleteUserLearnDailyPastLearnStatisticsJob',
            'SyncUserLearnDailyLearnStatisticsJob',
            'StorageDailyLearnStatisticsJob',
            'DistributorSyncJob',
            'DeleteFiredLogJob',
            'CheckConvertStatusJob',
            'updateCourseSetHotSeq',
            'CloudConsultFreshJob',
            'DeleteUserFootprintJob',
            'WechatSubscribeRecordSynJob',
            ], ArrayToolkit::column($result, 'name'));
    }

    public function testInitSystemUsers()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);
        ReflectionUtils::invokeMethod($initializer, '_initSystemUsers', []);
        $result = $this->getUserService()->searchUsers([], [], 0, \PHP_INT_MAX);
        $this->assertEquals(1, count($result));
        $this->assertArrayEquals([
            'email' => 'admin@admin.com',
            'nickname' => 'admin',
            'type' => 'default',
            'emailVerified' => '0',
            'roles' => [
                0 => 'ROLE_USER',
                1 => 'ROLE_ADMIN',
                2 => 'ROLE_SUPER_ADMIN',
                3 => 'ROLE_TEACHER',
            ],
            'orgId' => '1',
            'orgCode' => '1.',
        ], ArrayToolkit::parts($result[0], ['nickname', 'emailVerified', 'orgId', 'orgCode', 'email', 'password', 'type', 'roles']));
    }

    public function testInitFolders()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);
        ReflectionUtils::invokeMethod($initializer, 'initFolders', []);

        $folders = [
            $this->biz['kernel.root_dir'].'/data/udisk',
            $this->biz['kernel.root_dir'].'/data/private_files',
            $this->biz['kernel.root_dir'].'/../web/files',
        ];

        $filesystem = new Filesystem();

        foreach ($folders as $folder) {
            $this->assertTrue($filesystem->exists($folder));
        }
    }

    public function testInitRole()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);
        ReflectionUtils::invokeMethod($initializer, '_initRole', []);

        $result = $this->getRoleService()->searchRoles([], [], 0, \PHP_INT_MAX);

        $roles = ['ROLE_USER', 'ROLE_TEACHER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];
        $roleCodes = ArrayToolkit::column($result, 'code');

        $this->assertEquals(4, count($roleCodes));

        foreach ($roleCodes as $roleCode) {
            $this->assertTrue(in_array($roleCode, $roles));
        }
    }

    public function testinitLockFile()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);
        $initializer->initLockFile();
        $filesystem = new Filesystem();
        $files = [
            $this->biz['kernel.root_dir'].'/data/install.lock',
            $this->biz['kernel.root_dir'].'/config/routing_plugins.yml',
        ];

        foreach ($files as $file) {
            $this->assertTrue($filesystem->exists($file));
        }
    }

    public function testInitOrg()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);
        ReflectionUtils::invokeMethod($initializer, '_initOrg', []);
        $result = $this->getOrgService()->searchOrgs([], [], 0, \PHP_INT_MAX);

        $this->assertArrayEquals([
            'name' => '全站',
            'parentId' => '0',
            'childrenNum' => '0',
            'depth' => '1',
            'seq' => '0',
            'description' => null,
            'code' => 'FullSite',
            'orgCode' => '1.',
            'createdUserId' => '1',
        ], ArrayToolkit::parts($result[0], ['name', 'parentId', 'childrenNum', 'depth', 'description', 'seq', 'code', 'orgCode', 'createdUserId']));
    }

    public function testInitFile()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);
        ReflectionUtils::invokeMethod($initializer, '_initFile', []);

        $result = $this->getFileService()->getAllFileGroups();

        $this->assertArrayEquals([
            [
                'name' => '默认文件组',
                'code' => 'default',
                'public' => 1,
            ],
            [
                'name' => '缩略图',
                'code' => 'thumb',
                'public' => 1,
            ],
            [
                'name' => '课程',
                'code' => 'course',
                'public' => 1,
            ],
            [
                'name' => '用户',
                'code' => 'user',
                'public' => 1,
            ],
            [
                'name' => '课程私有文件',
                'code' => 'course_private',
                'public' => 0,
            ],
            [
                'name' => '资讯',
                'code' => 'article',
                'public' => 1,
            ],
            [
                'name' => '临时目录',
                'code' => 'tmp',
                'public' => 1,
            ],
            [
                'name' => '全局设置文件',
                'code' => 'system',
                'public' => 1,
            ],
            [
                'name' => '小组',
                'code' => 'group',
                'public' => 1,
            ],
            [
                'name' => '编辑区',
                'code' => 'block',
                'public' => 1,
            ],
            [
                'name' => '班级',
                'code' => 'classroom',
                'public' => 1,
            ],
        ], $result);
    }

    public function testInitCategory()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);

        ReflectionUtils::invokeMethod($initializer, '_initCategory', []);
        $courseGroup = $this->getCategoryService()->getGroupByCode('course');
        $courseCategory = $this->getCategoryService()->getCategoryByCode('default');
        $classroomGroup = $this->getCategoryService()->getGroupByCode('classroom');
        $classroomCategory = $this->getCategoryService()->getCategoryByCode('classroomdefault');

        $this->assertNotTrue(empty($courseGroup));
        $this->assertNotTrue(empty($courseCategory));
        $this->assertNotTrue(empty($classroomGroup));
        $this->assertNotTrue(empty($classroomCategory));
    }

    public function testInitTag()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);

        ReflectionUtils::invokeMethod($initializer, '_initTag', []);
        $result = $this->getTagService()->getTagByName('默认标签');

        $this->assertNotTrue(empty($result));
    }

    public function testInitRegisterSetting()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);

        ReflectionUtils::invokeMethod($initializer, 'initRegisterSetting', [['nickname' => 'test']]);
        $result = $this->getSettingService()->get('auth');

        $emailBody = <<<'EOD'
Hi, {{nickname}}

欢迎加入{{sitename}}!

请点击下面的链接完成注册：

{{verifyurl}}

如果以上链接无法点击，请将上面的地址复制到你的浏览器(如IE)的地址栏中打开，该链接地址24小时内打开有效。

感谢对{{sitename}}的支持！

{{sitename}} {{siteurl}}

(这是一封自动产生的email，请勿回复。)
EOD;
        $this->assertArrayEquals([
            'register_mode' => 'email',
            'email_activation_title' => '请激活您的{{sitename}}帐号',
            'email_activation_body' => trim($emailBody),
            'welcome_enabled' => 'opened',
            'welcome_sender' => 'test',
            'welcome_methods' => [],
            'welcome_title' => '欢迎加入{{sitename}}',
            'welcome_body' => '您好{{nickname}}，我是{{sitename}}的管理员，欢迎加入{{sitename}}，祝您学习愉快。如有问题，随时与我联系。',
        ], $result);
    }

    public function testInitStorageSetting()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);

        ReflectionUtils::invokeMethod($initializer, '_initStorageSetting', []);
        $result = $this->getSettingService()->get('storage');
        $this->assertArrayEquals([
            'upload_mode' => 'local',
            'cloud_api_server' => 'http://api.edusoho.net',
            'cloud_access_key' => '',
            'cloud_secret_key' => '',
        ], $result);
    }

    public function testInitDefaultSetting()
    {
        $initializer = new SystemInitializer(new ConsoleOutput());
        ReflectionUtils::invokeMethod($initializer, '_initDefaultSetting', []);

        $result = $this->getSettingService()->get('default');
        $this->assertArrayEquals([
            'chapter_name' => '章',
            'user_name' => '学员',
            'part_name' => '节',
        ], $result);
    }

    public function testInitPostNumRulesSetting()
    {
        $initializer = new SystemInitializer(new ConsoleOutput());
        ReflectionUtils::invokeMethod($initializer, '_initPostNumRulesSetting', []);

        $result = $this->getSettingService()->get('post_num_rules');
        $this->assertArrayEquals([
            'rules' => [
                'thread' => [
                    'fiveMuniteRule' => [
                        'interval' => 300,
                        'postNum' => 100,
                    ],
                ],
                'threadLoginedUser' => [
                    'fiveMuniteRule' => [
                        'interval' => 300,
                        'postNum' => 50,
                    ],
                ],
            ],
        ], $result);
    }

    public function testInitDeveloperSetting()
    {
        $initializer = new SystemInitializer(new ConsoleOutput());
        ReflectionUtils::invokeMethod($initializer, '_initDeveloperSetting', []);

        $result = $this->getSettingService()->get('developer');
        $this->assertArrayEquals([
            'cloud_api_failover' => 1,
        ], $result);
    }

    public function testInitPaymentSetting()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);

        ReflectionUtils::invokeMethod($initializer, '_initPaymentSetting', []);
        $result = $this->getSettingService()->get('payment');
        $this->assertArrayEquals([
            'enabled' => 0,
            'bank_gateway' => 'none',
            'alipay_enabled' => 0,
            'alipay_key' => '',
            'alipay_accessKey' => '',
            'alipay_secretKey' => '',
        ], $result);
    }

    public function testInitSiteSetting()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);

        ReflectionUtils::invokeMethod($initializer, '_initSiteSetting', []);
        $result = $this->getSettingService()->get('site');
        $this->assertArrayEquals([
            'name' => 'EDUSOHO测试站',
            'slogan' => '强大的在线教育解决方案',
            'url' => 'http://demo.edusoho.com',
            'logo' => '',
            'seo_keywords' => 'edusoho, 在线教育软件, 在线在线教育解决方案',
            'seo_description' => 'edusoho是强大的在线教育开源软件',
            'master_email' => 'test@edusoho.com',
            'icp' => ' 浙ICP备13006852号-1',
            'analytics' => '',
            'status' => 'open',
            'closed_note' => '',
        ], $result);
    }

    public function testInitRefundSetting()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);

        ReflectionUtils::invokeMethod($initializer, '_initRefundSetting', []);
        $result = $this->getSettingService()->get('refund');
        $this->assertArrayEquals([
            'maxRefundDays' => 10,
            'applyNotification' => '您好，您退款的{{item}}，管理员已收到您的退款申请，请耐心等待退款审核结果。',
            'successNotification' => '您好，您申请退款的{{item}} 审核通过，将为您退款{{amount}}元。',
            'failedNotification' => '您好，您申请退款的{{item}} 审核未通过，请与管理员再协商解决纠纷。',
        ], $result);
    }

    public function testInitConsultSetting()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);

        ReflectionUtils::invokeMethod($initializer, '_initConsultSetting', []);
        $result = $this->getSettingService()->get('contact');
        $this->assertArrayEquals([
            'enabled' => 0,
            'worktime' => '9:00 - 17:00',
            'qq' => [
                ['name' => '', 'number' => ''],
            ],
            'qqgroup' => [
                ['name' => '', 'number' => ''],
            ],
            'phone' => [
                ['name' => '', 'number' => ''],
            ],
            'webchatURI' => '',
            'email' => '',
            'color' => 'default',
        ], $result);
    }

    public function testInitMagicSetting()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);

        ReflectionUtils::invokeMethod($initializer, '_initMagicSetting', []);
        $result = $this->getSettingService()->get('magic');
        $this->assertArrayEquals([
            'export_allow_count' => 100000,
            'export_limit' => 10000,
            'enable_org' => 0,
        ], $result);
    }

    public function testInitMailerSetting()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);

        ReflectionUtils::invokeMethod($initializer, '_initMailerSetting', []);
        $result = $this->getSettingService()->get('mailer');
        $this->assertArrayEquals([
            'enabled' => 0,
            'host' => 'smtp.exmail.qq.com',
            'port' => '25',
            'username' => 'user@example.com',
            'password' => '',
            'from' => 'user@example.com',
            'name' => '',
        ], $result);
    }

    public function testInitAdminUser()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);

        $fields = [
            'email' => 'test@edusoho.com',
            'password' => 'test123',
            'nickname' => 'testnickname',
        ];
        $result = $initializer->initAdminUser($fields);

        $this->assertEquals('test@edusoho.com', $result['email']);
        $this->assertEquals('testnickname', $result['nickname']);
        $this->assertArrayEquals(['ROLE_USER', 'ROLE_TEACHER', 'ROLE_SUPER_ADMIN'], $result['roles']);
    }

    public function testInitCustom()
    {
        $output = new ConsoleOutput();
        $initializer = new SystemInitializer($output);

        $initializer->_initCustom();
    }

    /**
     * @return SettingService
     */
    private function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    /**
     * @return TagService
     */
    protected function getTagService()
    {
        return $this->createService('Taxonomy:TagService');
    }

    /**
     * @return CategoryService
     */
    protected function getCategoryService()
    {
        return $this->createService('Taxonomy:CategoryService');
    }

    protected function getCategoryDao()
    {
        return $this->createDao('Article:CategoryDao');
    }

    protected function getBlockDao()
    {
        return $this->createDao('Content:BlockDao');
    }

    private function getFileService()
    {
        return $this->createService('Content:FileService');
    }

    private function getContentService()
    {
        return $this->createService('Content:ContentService');
    }

    protected function getNavigationService()
    {
        return $this->createService('Content:NavigationService');
    }

    protected function getBlockService()
    {
        return $this->createService('Content:BlockService');
    }

    protected function getSchedulerService()
    {
        return $this->createService('Scheduler:SchedulerService');
    }

    /**
     * @return UserService
     */
    private function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getOrgService()
    {
        return $this->createService('Org:OrgService');
    }

    protected function getRoleService()
    {
        return $this->createService('Role:RoleService');
    }
}
