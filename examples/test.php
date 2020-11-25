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

$res = \Mine\Queue\QueueLib::instance()->publish(['a'=>'b'],true);

dump($res);

