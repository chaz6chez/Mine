<?php
/**
 * Who ?: Chaz6chez
 * How !: 250220719@qq.com
 * Where: http://chaz6chez.top
 * Time : 2019/6/5|14:10
 * What : Creating Fucking Bug For Every Code
 */

if (!defined('GLOBAL_START')){
    ini_set('date.timezone','Asia/Shanghai');

    define('ROOT_PATH', dirname(__DIR__));
    define('LOG_PATH', dirname(__DIR__));
    require_once ROOT_PATH . '/vendor/autoload.php';
    \Mine\Helper\Tools::LauncherDefines(__DIR__);
    \Mine\Helper\Tools::LauncherFunctions(__DIR__);
    \Mine\Helper\Tools::LauncherSupport(__DIR__,__DIR__);
}
use Workerman\Worker;
use Mine\Core\CoreServer;
# API server
$webServer = new CoreServer('Http://0.0.0.0:82');
$webServer->name = 'app_server';
$webServer->allowed     = ['Text'];
$webServer->forbidden   = ['Common'];
$webServer->defaultPath = 'text/index/index';
$webServer->basePath    = 'Example';

# 进程数
$webServer->count  = 8; # api 进程数
# 端口复用
$webServer->reusePort = DEBUG ? true : false;

# 定义根目录
$GLOBALS['SERVER_NAME'] = 'm0.test.com';
$webServer->addRoot($GLOBALS['SERVER_NAME'], PUBLIC_PATH);

if (!defined('GLOBAL_START')){
    $GLOBALS['SERVER_NAME_1'] = '127.0.0.1';
    $webServer->addRoot($GLOBALS['SERVER_NAME_1'], PUBLIC_PATH);
    Worker::$logFile = LOG_PATH."/{$webServer->name}.log";
    Worker::runAll();
}