<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/24            #
# -------------------------- #
namespace Mine\Queue;
use Mine\Core\Instance;

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
 *
 * @deprecated
 */
class MQConsumer extends Instance {

    protected function _initConfig(){
        // TODO: Implement _initConfig() method.
    }


    /**
     * 队列指路标
     * @param \AMQPEnvelope $even
     * @param \AMQPQueue $queue
     */
    public function MQRoute(\AMQPEnvelope $even,\AMQPQueue $queue){
        $msg = unserialize($body = $even->getBody());
        if(!is_array($msg)){
            log_add("Queue message error [{$body}]",'mq_consumer',__METHOD__);
            return;
        }
        $helper = MQRoute::factory($msg);
        $helper->validate();
        if($helper->hasError()){
            //缺失关键内容
            log_add("MQRoute error [{$body}][{$helper->getError()}]",'mq_consumer',__METHOD__);
            return;
        }
        $res = call_user_func([$this, $helper->target], $helper,$queue);
        if($res == false){
            //目标缺失
            log_add("MQRoute target missed",'mq_consumer',__METHOD__);
            return;
        }
        $res = $this->result($res);
        if($res->hasError()){
            //业务错误信息
            log_add("MQRoute service error [{$helper->target}][{$res->getMessage()}]",'mq_consumer',__METHOD__);
            return;
        }
        try{
            $queue->ack($even->getDeliveryTag());
        }catch (\AMQPConnectionException $e){
            //连接器异常
            log_add("MQRoute ack connection error",'mq_consumer',__METHOD__);
        }catch (\AMQPChannelException $e){
            //通道异常
            log_add("MQRoute ack channel error",'mq_consumer',__METHOD__);
        }
        return;
    }
}