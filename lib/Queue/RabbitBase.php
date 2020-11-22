<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/22            #
# -------------------------- #
namespace Mine\Queue;

use Mine\Core\Config;
use Mine\Core\Instance;
use Mine\Definition\Define;
use Mine\Helper\Exception;
use PhpAmqpLib\Channel\AMQPChannel;

class RabbitBase extends Instance {
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
    protected $_exchange_name;
    protected $_exchange_type;
    /**
     * @var \AMQPQueue
     */
    protected $_queue;
    protected $_queue_name;

    /**
     * @var \Exception
     */
    protected $_exception;

    /**
     * @var array 配置
     */
    public $_config = [
        'host'  => '127.0.0.1',
        'vhost' => '/',
        'port'  => 5672,
        'username' => '',
        'password' => '',
        'exchange' => '',
        'queue'    => '',
    ];

    /**
     * 配置
     */
    protected function _initConfig() {
        $this->_config = Config::get(Define::CONFIG_MQ);
        $this->_config = isset($this->_config['rabbit']) ? $this->_config['rabbit'] : [];
    }

    /**
     * 组件初始化
     */
    protected function _init(){
        parent::_init();
        self::ext();
    }

    /**
     * @throws Exception
     */
    public static function ext(){
        if(!extension_loaded('amqp')){
            throw new Exception('not support: amqp');
        }
    }

    /**
     * @throws \AMQPConnectionException
     */
    public function connection(){
        if(!$this->_connection instanceof \AMQPConnection){
            $this->_connection = new \AMQPConnection([
                'host'     => $this->_config['host'],
                'virtual'  => $this->_config['vhost'],
                'port'     => $this->_config['port'],
                'login'    => $this->_config['username'],
                'password' => $this->_config['password'],
            ]);
        }
        $this->_connection->connect();
    }

    /**
     * @throws \AMQPConnectionException
     */
    public function channel(){
        if(!$this->_channel instanceof \AMQPChannel){
            $this->_channel = new \AMQPChannel($this->_connection);
        }
        $this->_channel_id = $this->_channel->getChannelId();
    }

    /**
     * @param $name
     * @param $type
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     */
    public function exchange($name, $type){
        if(!$this->_exchange instanceof \AMQPExchange){
            $this->_exchange = new \AMQPExchange($this->_channel);
        }
        $this->_exchange->setName($this->_exchange_name = $name);
        $this->_exchange->setType($this->_exchange_type = $type);
    }

    /**
     * @param $name
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPQueueException
     */
    public function queue($name){
        if(!$this->_queue instanceof \AMQPQueue){
            $this->_queue = new \AMQPQueue($this->_channel);
        }
        $this->_queue->setName($this->_queue_name = $name);
        $this->_queue->bind($this->_exchange_name);
    }

    public function close(){
        if($this->_channel instanceof AMQPChannel){
            $this->_channel->close();
            $this->clean();
            return true;
        }
        return false;
    }

    public function clean(){
        $this->_connection = null;
        $this->_channel = null;
        $this->_channel_id = null;
        $this->_exchange = null;
        $this->_exchange_name = null;
        $this->_exchange_type = null;
        $this->_queue = null;
        $this->_queue_name = null;
        $this->_exception = null;
    }

    public function isConnected(){
        if($this->_channel instanceof AMQPChannel){
            return $this->_channel->isConnected();
        }
        return false;
    }

    public function getException(){
        return $this->_exception;
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
        return true;
    }
}