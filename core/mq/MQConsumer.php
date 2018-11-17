<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/24            #
# -------------------------- #
namespace core\mq;

use Api\Common\Struct\Helper\MQRoute;
use core\base\Service;

/**
 * 消费者路标类
 * 具体消费者业务请继承路标类
 *
 * $even->body 反序列化分解进入MQRoute Struct
 *
 * MQRoute->type string   业务类型 例：email phone
 * MQRoute->data string   业务数据 序列化的数组
 * MQRoute->target string 业务目标 指向子类内部方法，不存在则新增一条日志
 * MQRoute->source string 业务来源 具体的类名加方法 例：Service/User/login
 *
 * 仅成功会通知 $queue->ack()
 * 失败做日志记录 (文件日志 or 数据库日志)
 *
 * Class MQConsumer
 * @package core\mq
 */
class MQConsumer extends Service {
    /**
     * 队列指路标
     * @param \AMQPEnvelope $even
     * @param \AMQPQueue $queue
     */
    public function MQRoute(\AMQPEnvelope $even,\AMQPQueue $queue){
        $msg = unserialize($body = $even->getBody());
        if(!is_array($msg)){
            safe_echo(" [*]>> Queue message error\n");
            safe_echo(" [*]>> -> [$body]\n");
            //todo 日志记录该消息内容格式错误
            return;
        }
        $helper = MQRoute::factory($msg);
        $helper->validate();
        if($helper->hasError()){
            //todo 日志记录该消息内容缺失关键内容
            $error = $body . '->' . $helper->getError();
            safe_echo(" [*]>> MQRoute error\n");
            safe_echo(" [*]>> -> [$error]\n");
            return;
        }
        $res = call_user_func([$this, $helper->target], $helper,$queue);
        if($res == false){
            safe_echo(" >>[*] MQRoute target missed\n");
            //todo 日志记录该消息目标缺失
            return;
        }
        $res = $this->result($res);
        if($res->hasError()){
            $error = $res->getMessage();
            safe_echo(" [*]>> MQRoute service error\n");
            safe_echo(" [*]>> -> [$error]\n");
            //todo 日志记录业务错误信息
            return;
        }
        try{
            $queue->ack($even->getDeliveryTag());
        }catch (\AMQPConnectionException $e){
            safe_echo(" [*]>> MQRoute ack connection error\n");
            //todo 连接器异常
        }catch (\AMQPChannelException $e){
            safe_echo(" [*]>> MQRoute ack channel error\n");
            //todo 记录通道异常
        }

        safe_echo(" [*]>> Queue message success\n");
        return;
    }
}