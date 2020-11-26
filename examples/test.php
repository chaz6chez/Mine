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
$queue->_queue_name = 'ZBC';
$queue->_exchange_name = 'ZBC';
$res = $queue->publish(['a'=>'b'],true);
//
dump($res);
////use PhpAmqpLib\Connection\AMQPStreamConnection;
////use PhpAmqpLib\Message\AMQPMessage;
////
////$conf = [
////    'host' => '127.0.0.1',
////    'port' => 5672,
////    'user' => 'zbc',
////    'pwd' => 'zbc',
////    'vhost' => '/',
////];
////
////$conn = new AMQPStreamConnection( //建立生产者与mq之间的连接
////    $conf['host'], $conf['port'], $conf['user'], $conf['pwd'], $conf['vhost']
////);
////$channel = $conn->channel(); //在已连接基础上建立生产者与mq之间的通道
//$conn = $queue->connection();
//$channel = $conn->channel();
//
//$channel->exchange_declare($queue->_exchange_name, 'direct', false, true, false); //声明初始化交换机
//$channel->queue_declare($queue->_queue_name, false, true, false, false); //声明初始化一条队列
//$channel->queue_bind($queue->_queue_name, $queue->_exchange_name); //将队列与某个交换机进行绑定，并使用路由关键字
//
//$channel->close();
//$conn->close();

