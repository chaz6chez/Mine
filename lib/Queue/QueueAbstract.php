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

    /**
     * @var array 配置
     */
    protected static $_config = [
        'host'     => '127.0.0.1',
        'vhost'    => '/',
        'port'     => 5672,
        'username' => '',
        'password' => '',
        'exchange' => '',
        'queue'    => '',
    ];

    public static function config(array $config, $add = false){
        self::$_config = $add ? array_merge(self::$_config, $config) : $config;
    }

    /**
     * 配置
     */
    protected function _initConfig() {
        self::$_config = Config::get(Define::CONFIG_MQ);
        self::$_config = isset(self::$_config['rabbit']) ? self::$_config['rabbit'] : [];
        $this->setConfigs(self::$_config);
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
    }

    abstract public function connection();
    abstract public function exchange(string $name = null, string $type = null);
    abstract public function channel(int $count = 1);
    abstract public function queue(string $name = null);
    abstract public function closeConnection();
    abstract public function closeChannel();
}