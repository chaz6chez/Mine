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

$a = [
    'a' => 's',
    'b' => '1',
    'c' => '00'
];
$a = implode($a,',');
echo $a;
