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

$db = \Mine\Db\Db::instance();
$db->setConfigs([
       'clearing' => [
           'database_type' => 'mysql',
           'server'        => '127.0.0.1',
           'username'      => 'root',
           'password'      => 'root',
           'database_file' => '',
           'port'          => '3306',
           'charset'       => 'utf8',
           'database_name' => '3y_clearing',
           'option'        => [
               PDO::ATTR_EMULATE_PREPARES => true,  # 本地预处理
               PDO::ATTR_PERSISTENT       => false, # 长连接
               PDO::ATTR_TIMEOUT          => 2,
           ],
           'prefix'        => '',
           'slave'         => [
               'database_type' => 'mysql',
               'server'        => '127.0.0.1',
               'username'      => 'root',
               'password'      => 'root',
               'database_file' => '',
               'port'          => '3306',
               'charset'       => 'utf8',
               'database_name' => '3y_clearing',
               'option'        => [
                   PDO::ATTR_EMULATE_PREPARES => true,  # 本地预处理
                   PDO::ATTR_PERSISTENT       => false, # 长连接
                   PDO::ATTR_TIMEOUT          => 2,
               ],
               'prefix'        => '',
           ],
       ],
]);
dump($db->dbName('clearing')->table('user_assets')->where(['assets_id' => 1])->find(true));
dump($db->dbName('clearing')->last());

