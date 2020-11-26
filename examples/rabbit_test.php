<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2019/12/2            #
# -------------------------- #

if (!defined('GLOBAL_START')){
    ini_set('date.timezone','Asia/Shanghai');
    define('ROOT_PATH', dirname(__DIR__));
    define('LOG_PATH', dirname(__DIR__));
    require_once ROOT_PATH . '/vendor/autoload.php';
    \Mine\Helper\Tools::LauncherDefines(__DIR__);

}
\Mine\Helper\Tools::LauncherConfig(__DIR__. '/configs.php');
\Mine\Core\Config::init();

$queue = \Mine\Queue\QueueLib::instance();
$i = 0;
do{
    $i++;
    $res = $queue->publishUseRoute('queue_server',\Mine\Queue\QueueRoute::ENTRANCE, [
        'test' => 'test' . $i
    ]);
}while($i < 10000);
