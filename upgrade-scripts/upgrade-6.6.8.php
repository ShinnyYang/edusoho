<?php

use Symfony\Component\Filesystem\Filesystem;
use Topxia\Service\Common\ServiceKernel;
use Topxia\Common\BlockToolkit;
use Symfony\Component\Yaml\Yaml;

 class EduSohoUpgrade extends AbstractUpdater
 {
     public function update()
     {
         $this->getConnection()->beginTransaction();
         try {
             $this->updateScheme();
             $this->getConnection()->commit();

         } catch (\Exception $e) {
             $this->getConnection()->rollback();
             throw $e;
         }

        try {
             $dir = realpath(ServiceKernel::instance()->getParameter('kernel.root_dir')."/../web/install");
             $filesystem = new Filesystem();

             if (!empty($dir)) {
                 $filesystem->remove($dir);
             }
        } catch (\Exception $e) {
        }

         $developerSetting = $this->getSettingService()->get('developer', array());
         $developerSetting['debug'] = 0;

         ServiceKernel::instance()->createService('System.SettingService')->set('developer', $developerSetting);
         ServiceKernel::instance()->createService('Crontab.CrontabService')->setNextExcutedTime(time());

    }

    private function updateScheme()
    {
        $developerSetting = $this->getSettingService()->get('developer', array());
        $developerSetting['cloud_api_failover'] = 1;
        ServiceKernel::instance()->createService('System.SettingService')->set('developer', $developerSetting);
    }

    private function updateCrontabSetting()
    {
        $dir = realpath(ServiceKernel::instance()->getParameter('kernel.root_dir')."/../app/data/crontab_config.yml");
        $filesystem = new Filesystem();

        if (!empty($dir)) {
            $filesystem->remove($dir);
        }
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


     private function getSettingService()
     {
         return ServiceKernel::instance()->createService('System.SettingService');
     }


 }

 abstract class AbstractUpdater
 {
    protected $kernel;
     public function __construct($kernel)
     {
         $this->kernel = $kernel;
     }

     public function getConnection()
     {
         return $this->kernel->getConnection();
     }

     protected function createService($name)
     {
         return $this->kernel->createService($name);
     }

     protected function createDao($name)
     {
         return $this->kernel->createDao($name);
     }

   

     abstract public function update();
 }
