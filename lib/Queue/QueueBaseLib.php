<?php
namespace Mine\Queue;

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
    /**
     * @var \AMQPQueue
     */
    protected $_queue;
    /**
     * @var \Exception
     */
    protected $_exception;

    public function getQueue(){
        return $this->_queue;
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

    /**
     * @return \AMQPConnection
     * @throws \AMQPConnectionException
     */
    public function connection(){
        if(!$this->_connection instanceof \AMQPConnection){
            $this->_connection = new \AMQPConnection([
                'host'     => $this->getConfig('host'),
                'virtual'  => $this->getConfig('vhost'),
                'port'     => $this->getConfig('port'),
                'login'    => $this->getConfig('username'),
                'password' => $this->getConfig('password'),
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
     * @param $name
     * @param $type
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     */
    public function exchange(string $name, string $type){
        if(!$this->_exchange instanceof \AMQPExchange){
            $this->_exchange = new \AMQPExchange($this->_channel);
        }
        $this->_exchange->setName($name);
        $this->_exchange->setType($type);
    }

    /**
     * @param $name
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPQueueException
     */
    public function queue(string $name = null){
        if(!$this->_queue instanceof \AMQPQueue){
            $this->_queue = new \AMQPQueue($this->_channel);
        }
        $this->_queue->setName($name === null ? $this->_exchange->getName() : $name);
        $this->_queue->bind($this->_exchange->getName());
    }

    public function closeChannel(){
        if($this->_channel instanceof \AMQPChannel){
            $this->_channel->close();
            $this->clean();
            return true;
        }
        return false;
    }

    public function closeConnection(){
        if($this->_connection instanceof \AMQPConnection){
            $this->_connection->disconnect();
            $this->_connection = null;
            return true;
        }
        return false;
    }

    public function clean(){
        $this->_channel = null;
        $this->_channel_id = null;
        $this->_exchange = null;
        $this->_queue = null;
        $this->_exception = null;
    }

    public function createQueue($exchange_name, $exchange_type, $queue_name){
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