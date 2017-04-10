<?php

use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\Filesystem\Filesystem;


class PluginMigrate extends AbstractMigrate
{
    public function update($page)
    {
        $this->exec("delete from cloud_app where code = 'Homework';");

        $pluginFile = $this->getPluginConfig();
        $pluginFile = realpath($pluginFile);
        if (!empty($pluginFile)) {
            $config = require_once $pluginFile;
            if (isset($config['installed_plugins']['Homework'])) {
                $installedPlugins = $config['installed_plugins'];
                unset($installedPlugins['Homework']);
                $config['installed_plugins'] = $installedPlugins;
            }

            $config = is_array($config) ? $config : array();
            $config['active_theme_name'] = 'jianmo';

            $content = "<?php \n return " . var_export($config, true) . ";";
            $saved = file_put_contents($pluginFile, $content);
        } 

        ServiceKernel::instance()->createService('System.SettingService')->set('theme', array('uri' => 'jianmo'));

        $this->moveRoutingPluginsYml();
    }


    protected function getPluginConfig()
    {
        return ServiceKernel::instance()->getParameter('kernel.root_dir').'/../app/config/plugin.php';
    }

    protected function moveRoutingPluginsYml()
    {
        $file = ServiceKernel::instance()->getParameter('kernel.root_dir').'/../app/config/routing_plugins.yml';
        $targetFile = ServiceKernel::instance()->getParameter('kernel.root_dir').'/../app/config/old_routing_plugins.yml';
        $filesystem = new Filesystem();

        if ($filesystem->exists($file)) {
            $filesystem->copy($file, $targetFile, true);
            $filesystem->remove($file);
            $filesystem->touch($file);
        }
    }
}
