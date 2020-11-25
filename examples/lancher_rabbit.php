<?php
/**
 * Who ?: Chaz6chez
 * How !: 250220719@qq.com
 * Where: http://chaz6chez.top
 * Time : 2019/6/5|14:12
 * What : Creating Fucking Bug For Every Code
 */
if (!defined('GLOBAL_START')){
    ini_set('date.timezone','Asia/Shanghai');
    define('ROOT_PATH', dirname(__DIR__));
    define('LOG_PATH', dirname(__DIR__));
    require_once ROOT_PATH . '/vendor/autoload.php';
    \Mine\Helper\Tools::LauncherDefines(__DIR__);
    \Mine\Helper\Tools::LauncherConfig(__DIR__ . '/configs.php');
}
use Mine\Queue\QueueConsumers;
use Workerman\Worker;

$mqServer = new QueueConsumers();
$mqServer->_log_path = __DIR__ . '/log';

# 进程数
$mqServer->count   = 1;  # 消费者数量

if (!defined('GLOBAL_START')){
    Worker::$logFile = LOG_PATH."/{$webServer->name}.log";
    Worker::runAll();
}
