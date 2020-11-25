<?php
namespace Mine\Queue;

use Mine\Core\Config;
use Mine\Core\Instance;
use Mine\Definition\Define;

abstract class QueueAbstract extends Instance {
    const EXCHANGE_TYPE_DIRECT = 'direct';
    const EXCHANGE_TYPE_FANOUT = 'fanout';
    const EXCHANGE_TYPE_TOPIC  = 'topic';
    const EXCHANGE_TYPE_HEADER = 'header';

    const MESSAGE_DURABLE_YES  = 2;
    const MESSAGE_DURABLE_NO   = 1;

    public $_queue_name    = 'SEND';
    public $_exchange_name = 'SEND';
    public $_exchange_type = self::EXCHANGE_TYPE_DIRECT;

    protected $_config;
    /**
     * @var array 配置
     */
    protected static $config = [
        'host'     => '127.0.0.1',
        'vhost'    => '/',
        'port'     => 5672,
        'username' => '',
        'password' => '',
        'exchange' => '',
        'queue'    => '',
    ];

    public static function config(array $config, $add = false){
        self::$config = $add ? array_merge(self::$config, $config) : $config;
    }

    /**
     * 配置
     */
    protected function _initConfig() {
        self::$config = Config::get(Define::CONFIG_MQ);
        self::$config = isset(self::$config['rabbit']) ? self::$config['rabbit'] : [];
        $this->setConfigs(self::$config);
    }

    /**
     * 组件初始化
     */
    protected function _init(){
        parent::_init();
        self::ext();
    }

    /**
     * @throws \Exception
     */
    public static function ext(){
        if(!extension_loaded('amqp')){
            throw new \Exception('not support: amqp');
        }
        if(!extension_loaded('bcmath')){
            throw new \Exception('not support: bcmath');
        }
    }

    public static function decode(string $string){
        return json_decode($string, true);
    }

    public static function encode(array $message){
        return json_encode($message, JSON_UNESCAPED_UNICODE);
    }

    abstract public function connection();
    abstract public function exchange(string $name = null, string $type = null);
    abstract public function channel(int $count = 1);
    abstract public function queue(string $name = null);
    abstract public function closeConnection();
    abstract public function closeChannel();
}