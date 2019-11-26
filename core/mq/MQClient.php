<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/23            #
# -------------------------- #


namespace core\mq;

use Api\Common\Service\MQConsumers;
use core\lib\Config;
use Workerman\Lib\Timer;
use Workerman\Worker;

class MQClient extends Worker{

    public $configPath   = null;
    public $functionPath = null;
    private $eventLimit  = 20000;
    private $eventCount  = 0;
    private $interval    = 0.01;
    /**
     * @var \AMQPConnection
     */
    protected $connection = null;
    /**
     * @var \AMQPChannel
     */
    protected $channel = null;
    /**
     * @var \AMQPQueue
     */
    protected $queue = null;
    /**
     * @var Rabbit
     */
    protected $client = null;

    /**
     * @var Timer
     */
    protected $timer = null;

    /**
     * MQServer constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->name = 'MQClient';
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
        $this->funcInit();
        $this->confInit();
        cli_echo_debug("Rabbit Server Start","# : {$worker->workerId}|{$worker->id}");
        $rabbit = Rabbit::instance();
        $this->connection = new \AMQPConnection([
            'host'     => $rabbit->_config['host'],
            'virtual'  => $rabbit->_config['vhost'],
            'port'     => $rabbit->_config['port'],
            'login'    => $rabbit->_config['username'],
            'password' => $rabbit->_config['password'],
        ]);
        try{
            $this->connection->connect();
            $this->channel = new \AMQPChannel($this->connection);
            $exchange = new \AMQPExchange($this->channel);
            $exchange->setName($rabbit->_exchangeName);
            $exchange->setType($rabbit->_type);
            $this->queue = new \AMQPQueue($this->channel);
            $this->queue->setName($rabbit->_queueName);
            $this->queue->bind($rabbit->_exchangeName);
        }catch (\Exception $e){
            $error = $e->getMessage();
            log_add("[$worker->workerId|$worker->id] Rabbit Server Error [$error]",'mq_client',__METHOD__);
            cli_echo_debug("Rabbit Server Error [$error]","# : {$worker->workerId}|{$worker->id}");
            return;
        }
        $this->timer = Timer::add($this->interval,function(){
            $this->restart();
//            $this->queueConsume(); # 阻塞调用
            $this->queueGet(); # 非阻塞调用
        });
    }

    /**
     * @param float $interval
     */
    public function setInterval(float $interval){
        $this->interval = $interval;
    }

    /**
     * @param int $limit
     */
    public function setEventLimit(int $limit){
        $this->eventLimit = $limit;
    }

    /**
     * 队列消费
     *
     * 阻塞
     */
    protected function queueConsume(){
        try{
            // 阻塞调用
            $this->queue->consume(function (\AMQPEnvelope $even,\AMQPQueue $queue){
                MQConsumers::instance()->MQRoute($even,$queue);
            });
        }catch (\Exception $e){
            $error = $e->getMessage();
            cli_echo_debug("Consumers Error [$error]","#");
        }
    }

    /**
     * 队列消费
     *
     * 非阻塞
     */
    protected function queueGet(){
        try{
            // 非阻塞调用
            if($even = $this->queue->get()){
                MQConsumers::instance()->MQRoute($even,$this->queue);
            }
        }catch (\Exception $e){
            $error = $e->getMessage();
            cli_echo_debug("Consumers Error [$error]","#");
        }
    }

    /**
     * 计数重启
     */
    private function restart(){
        if(++$this->eventCount > $this->eventLimit){
            $this->eventCount = 0;
            Worker::stopAll();
        }
    }

    /**
     * stop
     * @param Worker $worker
     */
    public function onWorkerStop($worker){
        Timer::del($this->timer);
        $this->queue = null;
        $this->channel = null;
        ($this->connection instanceof \AMQPConnection) ? $this->connection->disconnect() : $this->connection = null;
        cli_echo_debug("Rabbit Server Stop","# : {$worker->workerId}|{$worker->id}");
    }
}