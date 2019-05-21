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
                require_once $this->functionPath;
            }
        }
    }

    /**
     * start
     * @param Worker $worker
     */
    public function onWorkerStart($worker){
        global $connection;
        global $channel;
        global $queue;
        $this->funcInit();
        $this->confInit();
        cli_echo_debug("Rabbit Server Start","# : {$worker->workerId}|{$worker->id}");
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

            $channel = new \AMQPChannel($connection);
            $exchange = new \AMQPExchange($channel);
            $exchange->setName($rabbit->_exchangeName);
            $exchange->setType($rabbit->_type);
            $queue = new \AMQPQueue($channel);
            $queue->setName($rabbit->_queueName);
            $queue->bind($rabbit->_exchangeName);

        }catch (\Exception $e){
            $error = $e->getMessage();
            log_add("[$worker->workerId|$worker->id] Rabbit Server Error [$error]",'MQ',__METHOD__);
            cli_echo_debug("Rabbit Server Error [$error]","# : {$worker->workerId}|{$worker->id}");
            return;
        }
        $count = 0;
        while(true){
            if(++$count > 20000){
                $worker::stopAll();
                break;
            }
            try{
                $queue->consume(function (\AMQPEnvelope $even,\AMQPQueue $queue){
                    MQConsumers::instance()->MQRoute($even,$queue);
                });
            }catch (\Exception $e){
                $error = $e->getMessage();
                cli_echo_debug("Consumers Error [$error]","#");
            }

        }
    }

    /**
     * stop
     * @param Worker $worker
     */
    public function onWorkerStop($worker){
        global $connection;
        global $channel;
        global $queue;
        $queue = null;
        ($channel instanceof \AMQPChannel and $channel) ? $channel->close() : $channel = null;
        ($connection instanceof \AMQPConnection and $connection) ? $connection->disconnect() : $connection = null;
        cli_echo_debug("Rabbit Server Stop","# : {$worker->workerId}|{$worker->id}");
    }
}