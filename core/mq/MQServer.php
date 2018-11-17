<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/23            #
# -------------------------- #
namespace core\mq;

use Api\Common\Service\MQConsumers;
use core\lib\Config;
use Workerman\Worker;

class MQServer extends Worker{

    public $configPath   = null;
    public $functionPath = null;
    /**
     * @var Rabbit
     */
    protected $client = null;

    /**
     * MQServer constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->name = 'MQServer';
    }

    /**
     * run
     */
    public function run() {
        $this->onWorkerStart = [$this, 'onWorkerStart'];
        $this->onWorkerStop = [$this, 'onWorkerStop'];
        parent::run();
    }

    /**
     * config init
     */
    public function confInit(){
        if($this->configPath){
            if(file_exists($this->configPath)){
                Config::load($this->configPath);
            }
        }else{
            Config::init();
        }
    }

    /**
     * function init
     */
    public function funcInit(){
        if($this->functionPath){
            if(file_exists($this->functionPath)){
                require_once COMMON_PATH . '/functions.php';
            }
        }
    }

    /**
     * start
     */
    public function onWorkerStart(){
        global $connection;
        global $channel;
        $this->funcInit();
        $this->confInit();
        self::safeEcho(" [#] Rabbit Server Start\n");
        $rabbit = Rabbit::instance();
        $connection = new \AMQPConnection([
            'host'     => $rabbit->_config['host'],
            'virtual'  => $rabbit->_config['vhost'],
            'port'     => $rabbit->_config['port'],
            'login'    => $rabbit->_config['username'],
            'password' => $rabbit->_config['password'],
        ]);
        try{
            $connection->connect();
        }catch (\AMQPConnectionException $e){
            $error = $e->getMessage();
            self::safeEcho(" [#] Rabbit Server Error [$error]\n");
            exit;
        }
        $channel = new \AMQPChannel($connection);
        $exchange = new \AMQPExchange($channel);
        $exchange->setName($rabbit->_exchangeName);
        $exchange->setType($rabbit->_type);
        $queue = new \AMQPQueue($channel);
        $queue->setName($rabbit->_queueName);
        $queue->bind($rabbit->_exchangeName);
        while(true){
            $queue->consume(function (\AMQPEnvelope $even,\AMQPQueue $queue){
                self::safeEcho(" [#]> Consumers start work\n");
                MQConsumers::instance()->MQRoute($even,$queue);
                self::safeEcho(" [#]> Consumers stop work\n");
            });
        }
    }

    /**
     * stop
     */
    public function onWorkerStop(){
        global $connection;
        global $channel;
        $channel->close();
        $connection->close();
        self::safeEcho(" [#] Rabbit Server Stop\n");
    }
}