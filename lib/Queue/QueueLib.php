<?php
namespace Mine\Queue;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class QueueLib extends QueueAbstract {
    /**
     * @var AMQPStreamConnection
     */
    private $_connection = null;
    /**
     * @var AMQPChannel
     */
    private $_channel    = null;
    /**
     * @var bool 判断交换机是否存在
     */
    protected $_passive       = true;
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
    public function channel(int $count = 1){
        if(!$this->_channel instanceof AMQPChannel){
            $this->_channel = $this->connection()->channel();
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
        $this->_channel->queue_bind($this->_queue_name,$this->_exchange_name);


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

    public function publish(array $message, $close_connect = false){
        try{
            $this->queue();
            $message = new AMQPMessage(
                self::encode($message),
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
            return [false,$exception->getMessage()];
        }
        return [true,null];
    }

    public function get($prefetch_count = 1){
        try{
            $this->queue(true);
            $this->channel()->basic_qos(null, $prefetch_count, null);
            $message = $this->channel()->basic_get($this->_queue_name);
        }catch(\Exception $exception){
            return [false,$exception->getMessage()];
        }

        return [true,$message];
    }

    public function ack($delivery_tag, $multiple = false){
        try{
            $this->queue(true);
            $this->channel()->basic_ack($delivery_tag, $multiple);
        }catch(\Exception $exception){
            return [false,$exception->getMessage()];
        }

        return [true,null];
    }

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