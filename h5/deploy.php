<?php
namespace Deployer;

use Symfony\Component\Yaml\Yaml;

require 'recipe/common.php';

// Configuration

set('repository', 'git@coding.codeages.work:edusoho/edusoho-h5.git');
set('git_tty', false); // [Optional] Allocate tty for git on first deployment
set('writable_mode', 'acl');
$yaml = Yaml::parse(file_get_contents(__DIR__.'/deploy.yml'));

$host = host($yaml['hosts'][0])
    ->stage($yaml['stage'])
    ->user($yaml['ssh']['user'])
    ->identityFile($yaml['ssh']['identity_file'])
    ->set('deploy_path', $yaml['path']);

foreach ($yaml['ssh']['options'] as $key => $option){
    $host->addSshOption($key, $option);
}

//host('124.160.104.77')
//    ->stage('dev')
//    ->user('root')
//    ->identityFile('~/.ssh/deployerkey')
//    ->addSshOption('UserKnownHostsFile', '/dev/null')
//    ->addSshOption('StrictHostKeyChecking', 'no')
//    ->set('deploy_path', '/var/www/h5.st.edusoho.cn');

// Tasks

desc('Build frontend');
task('frontend:build', function() {
    run('cnpm install --production');
    run('cnpm run build');
})->local();

desc('Upload frontend compiled files');
task('frontend:upload', function() {
    desc('Upload frontend compiled files');
    upload('./dist/', '{{release_path}}');
});

desc('Deploy your project');
task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
]);

before('deploy:prepare', 'frontend:build');
after('deploy:release', 'frontend:upload');

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');
