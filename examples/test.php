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
    \Mine\Helper\Tools::LauncherFunctions(__DIR__);
}

try{
    $a = new \Mine\Helper\SnowFlake(1,2,3);
    echo $a->nextId();
}catch (Exception $exception){
    echo $exception->getMessage();
}
