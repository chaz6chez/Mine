<?php
namespace Mine\Queue;

use Mine\Core\Config;
use Mine\Definition\Define;

/**
 * 基于 amqp 拓展开发的amqp库
 *
 * Class QueueBaseLib
 * @package Mine\Queue
 */
class QueueBaseLib extends QueueAbstract {
    /**
     * @var \AMQPConnection
     */
    protected $_connection;
    /**
     * @var \AMQPChannel
     */
    protected $_channel;
    protected $_channel_id;
    /**
     * @var \AMQPExchange
     */
    protected $_exchange;
    protected $_route_key = null;
    /**
     * @var \AMQPQueue
     */
    protected $_queue;
    protected $_declare = true;
    /**
     * @var \Exception
     */
    protected $_exception;

    public function setRouteKey(string $routeKey){
        $this->_route_key = $routeKey;
    }
    public function getRouteKey(){
        return $this->_route_key;
    }
    public function getExchange(){
        return $this->_exchange;
    }
    public function getException(){
        return $this->_exception;
    }
    public function getChannelId() {
        return $this->_channel_id;
    }
    public function declare(bool $declare){
        $this->_declare = $declare;
    }

    public static function requeue(){
        return AMQP_REQUEUE;
    }

    /**
     * @param float $timeout
     * @return \AMQPConnection
     * @throws \AMQPConnectionException
     */
    public function connection(float $timeout = 3.0){
        if(!$this->_connection instanceof \AMQPConnection){
            $this->_connection = new \AMQPConnection([
                'host'            => $this->getConfig('host'),
                'virtual'         => $this->getConfig('vhost'),
                'port'            => $this->getConfig('port'),
                'login'           => $this->getConfig('username'),
                'password'        => $this->getConfig('password'),
                'connect_timeout' => $timeout
            ]);
        }
        $this->_connection->connect();
        return $this->_connection;
    }

    /**
     * @param int $count
     * @return \AMQPChannel
     * @throws \AMQPConnectionException
     */
    public function channel(int $count = 1){
        if(!$this->_channel instanceof \AMQPChannel){
            $this->_channel = new \AMQPChannel($this->_connection);
            $this->_channel->qos(null, $count);
        }
        $this->_channel_id = $this->_channel->getChannelId();
        return $this->_channel;
    }

    /**
     * @param string|null $name
     * @param string|null $type
     * @return \AMQPExchange
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     */
    public function exchange(string $name = null, string $type = null){
        if(!$this->_exchange instanceof \AMQPExchange){
            $this->_exchange = new \AMQPExchange($this->_channel);
        }
        $this->_exchange->setName($this->_exchange_name = $name !== null ? $name : $this->_exchange_name);
        $this->_exchange->setType($this->_exchange_type = $type !== null ? $type : $this->_exchange_type);
        if($this->_declare){
            $this->_exchange->setFlags(AMQP_DURABLE);
            $this->_exchange->declareExchange();
        }
        return $this->_exchange;
    }

    /**
     * @param string|null $name
     * @return \AMQPQueue
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPQueueException
     */
    public function queue(string $name = null){
        if(!$this->_queue instanceof \AMQPQueue){
            $this->_queue = new \AMQPQueue($this->_channel);
        }
        $this->_queue->setName($this->_queue_name = $name === null ? $this->_queue_name : $name);
        if($this->_declare){
            $this->_queue->setFlags(AMQP_DURABLE);
            $this->_queue->declareQueue();
        }
        $this->_queue->bind($this->_exchange_name, $this->_route_key);
        return $this->_queue;
    }

    public function closeChannel(){
        if($this->_channel instanceof \AMQPChannel){
            $this->_channel->close();
            $this->clean();
            return true;
        }
        return false;
    }

    /**
     * 关闭connection
     * @return bool
     */
    public function closeConnection(){
        if($this->_connection instanceof \AMQPConnection){
            $this->_connection->disconnect();
            $this->_connection = null;
            return true;
        }
        return false;
    }

    public function publish($message, $close_connect = false){
        try{
            $this->createQueue();
            $this->_exchange->publish(
                is_array($message) ? self::encode($message) : (string)$message,
                $this->_route_key,
                AMQP_NOPARAM,
                [
                    'content_type' => 'text/plain',
                    'delivery_mode' => self::DELIVERY_MODE_PERSISTENT
                ]
            );
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
                return $this->publish($message, true);
            }
        }catch(\Exception $exception){
            return [false, "Server Exception [{$exception->getMessage()}]"];
        }
        return [false, 'Server Exception'];
    }

    /**
     * 清洗
     */
    public function clean(){
        $this->_channel = null;
        $this->_channel_id = null;
        $this->_exchange = null;
        $this->_queue = null;
        $this->_exception = null;
    }

    /**
     * 创建队列
     * @param null $exchange_name
     * @param null $exchange_type
     * @param null $queue_name
     * @return \AMQPQueue|bool
     */
    public function createQueue($exchange_name = null, $exchange_type = null, $queue_name = null){
        try{
            $this->connection();
            $this->channel();
            $this->exchange($exchange_name,$exchange_type);
            $this->queue($queue_name);
        }catch (\Exception $exception){
            $this->_exception = $exception;
            return false;
        }
        $this->_exception = null;
        return $this->_queue;
    }
}