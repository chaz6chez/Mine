<?php
namespace Mine\Queue;

use Mine\Core\Config;
use Mine\Definition\Define;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * 基于PHPAmqpLib的amqp库
 *
 * Class QueueLib
 * @package Mine\Queue
 */
class QueueLib extends QueueAbstract {
    /**
     * @var AMQPStreamConnection|null
     */
    protected $_connection = null;
    /**
     * @var AMQPChannel|null
     */
    protected $_channel    = null;
    /**
     * @var bool 判断交换机是否存在
     *      false:没有就新增
     *      true:没有就会抛出404异常
     */
    protected $_passive       = false;
    /**
     * @var bool 持久化
     */
    protected $_durable       = true;
    /**
     * @var bool 消费离线是否自动删除消息
     */
    protected $_auto_delete   = false;

    /**
     * 连接
     * @return AMQPStreamConnection
     * @throws \Exception
     */
    public function connection(){
        if(!$this->getConfig()){
            throw new \Exception('config error', -500);
        }
        if(!$this->_connection or !$this->_connection instanceof AMQPStreamConnection){
            $this->_connection = new AMQPStreamConnection(
                $this->getConfig('host'),
                $this->getConfig('port'),
                $this->getConfig('username'),
                $this->getConfig('password'),
                $this->getConfig('vhost')
            );
        }
        if(!$this->_connection->isConnected()){
            $this->_connection->reconnect();
            if(!$this->_connection->isConnected()){
                throw new \Exception('queue connect failed', -500);
            }
        }
        return $this->_connection;
    }

    /**
     * 获取信道
     * @param int $count
     * @return AMQPChannel
     * @throws \Exception
     */
    public function channel(int $count = 0){
        if(!$this->_channel instanceof AMQPChannel){
            $this->_channel = $this->connection()->channel();
        }
        if($count > 0){
            $this->_channel->basic_qos(null, $count, null);
        }
        return $this->_channel;
    }

    /**
     * @param string|null $name
     * @param string|null $type
     * @return $this
     * @throws \Exception
     */
    public function exchange(string $name = null, string $type = null){
        $this->channel()->exchange_declare(
            $this->_exchange_name = $name !== null ? $name : $this->_exchange_name,
            $this->_exchange_type = $type !== null ? $type : $this->_exchange_type,
            $this->_passive,
            $this->_durable,
            $this->_auto_delete
        );
        return $this;
    }

    /**
     * @param string|null $name
     * @return $this
     * @throws \Exception
     */
    public function queue(string $name = null){
        $this->channel()->queue_declare(
            $this->_queue_name = $name !== null ? $name : $this->_queue_name,
            $this->_passive,
            $this->_durable,
            false,
            $this->_auto_delete
        );
        $this->channel()->queue_bind($this->_queue_name,$this->_exchange_name);
        return $this;
    }

    /**
     * 关闭连接
     */
    public function closeConnection(){
        if($this->_connection instanceof AMQPStreamConnection){
            $this->_connection->close();
            $this->_connection = null;
        }
    }

    /**
     * 关闭信道
     */
    public function closeChannel(){
        if($this->_channel instanceof AMQPChannel){
            $this->_channel->close();
            $this->_channel = null;
        }
    }

    /**
     * 基础发布消息
     * @param $message
     * @param bool $close_connect
     * @return array
     */
    public function publish($message, $close_connect = false){
        try{
            $this->exchange()->queue();
            $message = new AMQPMessage(
                is_array($message) ? self::encode($message) : (string)$message,
                [
                    'content_type' => 'text/plain',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
                ]
            );
            $this->channel()->basic_publish($message, $this->_exchange_name);
            $this->closeChannel();
            if($close_connect){
                $this->closeConnection();
            }
        }catch(\Exception $exception){
            return [false,$exception->getMessage() .':'. $exception->getCode()];
        }
        return [true,null];
    }

    /**
     * 基于 QueueRoute 发布消息
     * @param string $server_name
     * @param string $method
     * @param array $params
     * @return array
     */
    public function publishUseRoute(string $server_name, string $method = QueueRoute::ENTRANCE, array $params = []){
        $config = Config::get(Define::CONFIG_QUEUE);
        if(
            !isset($config[$server_name]) or
            !isset($config[$server_name]['route'])
        ){
            return [false,"Server Not Found [{$server_name}]"];
        }
        $route = $config[$server_name]['route'];
        if(
            !class_exists($route) or
            !is_subclass_of($route, QueueRoute::class)
        ){
            return [false,"Server Illegal [{$server_name}]"];
        }
        try {
            $route = call_user_func([$route, 'instance']);
            if($route instanceof QueueRoute){
                $this->_queue_name = $route->getQueueName();
                $this->_exchange_type = $route->getExchangeType();
                $this->_exchange_name = $route->getExchangeName();
                if(!$route->verify($message = self::encode([
                    'method' => $method,
                    'params' => $params
                ]))){
                    return [false, 'Route Params Illegal'];
                }
                return $this->publish($message);
            }
        }catch(\Exception $exception){
            return [false, "Server Exception [{$exception->getMessage()}]"];
        }
        return [false, 'Server Exception'];
    }

    /**
     * 获取消息
     * @param int $prefetch_count
     * @return array
     */
    public function get($prefetch_count = 1){
        try{
            $this->queue(true);
            if($prefetch_count > 0){
                $this->channel()->basic_qos(null, $prefetch_count, null);
            }
            $message = $this->channel()->basic_get($this->_queue_name);
        }catch(\Exception $exception){
            return [false,$exception->getMessage()];
        }
        return [true,$message];
    }

    /**
     * 消息ACK
     * @param $delivery_tag
     * @param bool $multiple
     * @return array
     */
    public function ack($delivery_tag, $multiple = false){
        try{
            $this->queue(true);
            $this->channel()->basic_ack($delivery_tag, $multiple);
        }catch(\Exception $exception){
            return [false,$exception->getMessage()];
        }

        return [true,null];
    }

    /**
     * 消息NACK
     * @param $delivery_tag
     * @param bool $multiple
     * @param bool $requeue
     * @return array
     */
    public function nack($delivery_tag, $multiple = false, $requeue = false){
        try{
            $this->queue(true);
            $this->channel()->basic_nack($delivery_tag, $multiple, $requeue);
        }catch(\Exception $exception){
            return [false,$exception->getMessage()];
        }

        return [true,null];
    }
}