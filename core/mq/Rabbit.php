<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/22            #
# -------------------------- #
namespace core\mq;

use core\helper\Exception;
use core\lib\Config;
use core\lib\Instance;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;

class Rabbit extends Instance {

    /**
     * @var AMQPStreamConnection
     */
    private $_connection = null;

    /**
     * @var AMQPChannel
     */
    private $_channel = null;

    private $_channelId = '';

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

    public $_message      = [];
    public $_queueName    = 'SEND';
    public $_exchangeName = 'SEND';
    public $_type         = 'direct';
    public $_passive      = true;
    public $_durable      = true;
    public $_autoDelete   = true;

    private $_lastMsg = [];

    const EXCHANGE_TYPE_DIRECT = 'direct';
    const EXCHANGE_TYPE_FANOUT = 'fanout';
    const EXCHANGE_TYPE_TOPIC  = 'topic';
    const EXCHANGE_TYPE_HEADER = 'header';
    const MESSAGE_DURABLE_YES  = 2;
    const MESSAGE_DURABLE_NO   = 1;

    /**
     * 配置
     */
    protected function _initConfig() {
        $this->_config = Config::get('mq');
        $this->_config = isset($this->_config['rabbit']) ? $this->_config['rabbit'] : [];
    }

    /**
     * 组件初始化
     */
    public function _init(){
        parent::_init();
        if(!extension_loaded('amqp')){
            wm_500('not support: amqp');
        }
    }

    /**
     * 连接
     * @return AMQPStreamConnection
     * @throws Exception
     */
    private function _connection(){
        if(!$this->_connection or !$this->_connection instanceof AMQPStreamConnection){
            try{
                $this->_connection = new AMQPStreamConnection(
                    $this->_config['host'],
                    $this->_config['port'],
                    $this->_config['username'],
                    $this->_config['password'],
                    $this->_config['vhost']
                );
            }catch (\Exception $e){
                throw new Exception("rabbitMQ server connect error [{$e->getMessage()}]");
            }

        }
        return $this->_connection;
    }

    /**
     * 激活链接
     * @return $this
     * @throws Exception
     */
    public function active(){
        $this->_connection();
        return $this;
    }

    /**
     * 设置
     * @param $type
     * @param bool $passive
     * @param bool $durable
     * @param bool $autoDelete
     * @return $this
     */
    public function setParam($type,$passive = false,$durable = true,$autoDelete = false){
        $this->_type = $type;
        $this->_passive = $passive;
        $this->_durable = $durable;
        $this->_autoDelete = $autoDelete;
        return $this;
    }

    /**
     * 获取连接
     * @return AMQPStreamConnection
     */
    public function getConnection(){
        return $this->_connection;
    }

    /**
     * 获取信道
     * @param int $channelId
     * @return AMQPChannel
     */
    public function getChannel($channelId = null){
        if(!$channelId){
            return $this->_channel = $this->_connection->channel();
        }else{
            $this->_channelId = $channelId;
            return $this->_channel = $this->_connection->channel($channelId);
        }
    }


    /**
     * @return $this
     */
    public function exchangeDeclare(){

        $this->_channel->exchange_declare(
            $this->_exchangeName,
            $this->_type,
            $this->_passive,
            $this->_durable,
            $this->_autoDelete
        );
        return $this;
    }

    /**
     * @return $this
     */
    public function queueDeclare(){
        $this->_channel->queue_declare(
            $this->_queueName,
            $this->_passive,
            $this->_durable,
            $this->_autoDelete
        );
        return $this;
    }

    /**
     * 绑定队列
     * @return $this
     */
    public function queueBind(){
        $this->_channel->queue_bind($this->_queueName,$this->_exchangeName);
        return $this;
    }

    /**
     * @param string $callback
     * @return $this
     */
    public function basicConsume($callback = null){

        if($callback){
            $this->_channel->basic_consume(
                $this->_queueName,
                $this->_config['tag'],
                false,
                true,
                false,
                false,
                $callback);
        }else{
            $this->_channel->basic_consume(
                $this->_queueName,
                $this->_config['tag'],
                false,
                true,
                false,
                false
            );
        }
        return $this;
    }

    /**
     * 关闭连接
     * @return bool
     */
    public function closeConnection(){
        if($this->_connection instanceof AMQPStreamConnection){
            $this->_connection->close();
            $this->_connection = null;
//            $this->cleanUp();
            return true;
        }
        return false;
    }

    /**
     * 关闭信道
     * @return bool
     */
    public function closeChannel(){
        if($this->_channel instanceof AMQPChannel){
            $this->_channel->close();
            $this->_channel = null;
//            $this->cleanUp();
            return true;
        }
        return false;
    }

    /**
     * 清除
     */
    protected function cleanUp(){
        $this->_lastMsg = object2array($this);
        $this->_queueName = '';
        $this->_exchangeName = '';
        $this->_type = 'direct';
        $this->_passive = false;
        $this->_durable = true;
        $this->_autoDelete = true;
    }

    /**
     * 发布
     * @param array $message
     * @return array
     * array[0] => 标识 true:成功 false:失败
     * array[1] => 消息 失败时为错误消息，成功时是返回数据
     */
    public function publishMessage(array $message){
        $message = serialize($message);
        try{
          $this->active();
        }catch (Exception $e){
            if(defined('DEBUG') and DEBUG){
                cli_echo($e->getMessage(),'RABBIT');
            }
            return [false,$e->getMessage()];
        }
        $this->_channel = $this->_connection->channel();

        $this->queueDeclare()->exchangeDeclare()->queueBind();
        $msg = new AMQPMessage($message, [
            'content_type' => 'text/plain',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);
        $this->getChannel()->basic_publish($msg,$this->_exchangeName);
        $this->closeChannel();
        return [true,null];
    }

    /**
     * 拉取消息
     * @return array
     * array[0] => 标识 true:成功 false:失败
     * array[1] => 消息 失败时为错误消息，成功时是返回数据
     */
    public function getMessage(){
        try{
            $this->active();
        }catch (Exception $e){
            if(defined('DEBUG') and DEBUG){
                cli_echo($e->getMessage(),'RABBIT ERROR');
            }
            return [false,$e->getMessage()];
        }
        $this->queueDeclare()->exchangeDeclare()->queueBind();
        $msg = $this->getChannel()->basic_get($this->_queueName);
        $mix = null;
        if($msg){
            $this->getChannel()->basic_ack($msg->delivery_info['delivery_tag']);
            $mix = $msg->body;
        }
        $this->closeChannel();
        return [true,$mix];
    }

}