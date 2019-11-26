<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/23            #
# -------------------------- #
namespace Mine\Queue;

use Mine\Core\Config;
use Mine\Definition\Define;
use Mine\Helper\Tools;
use Workerman\Lib\Timer;
use Workerman\Worker;

class MQServer extends Worker{

    /**
     * @var array
     *
     *  WorkerName => [
     *      'service'         => 服务的组件地址 例:Api\V1\Service\Trade,
     *      'function'        => 上半区执行方法 例:,
     * ]
     */
    protected $config       = [];
    protected $service      = null;
    protected $serviceObj   = null;
    protected $action       = null;
    protected $configPath   = null;
    protected $functionPath = null;

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
     * @param string $name
     */
    public function setName(string $name){
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName() : string {
        return $this->name;
    }

    /**
     * MQServer constructor.
     */
    public function __construct() {
        parent::__construct();
        if($this->name == 'none'){
            $this->setName('queue_server');
        }
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
        Config::init();
        if($this->configPath){
            if(file_exists($this->configPath)){
                Config::load($this->configPath);
            }
        }
        $config = Config::get(Define::CONFIG_QUEUE);
        $this->config = isset($config[$this->getName()]) ? $config[$this->getName()] : [];
        $this->service = isset($this->config['service']) ? $this->config['service'] : null;
        $this->action = isset($this->config['function']) ? $this->config['function'] : null;
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
        Tools::SafeEcho("Rabbit Server Start","# : {$worker->workerId}|{$worker->id}");
        $this->_checker($worker);

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
            $this->_stop($worker,$error);
            return;
        }
        $this->timer = Timer::add($this->interval,function(){
            $this->restart();
            $this->queueConsume(); # 非阻塞调用
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
     * 非阻塞
     */
    protected function queueConsume(){
        try{
            // 非阻塞调用
            if($even = $this->queue->get()){
                list($key, $res) = call_user_func([$this->serviceObj,$this->action],[$even,$this->queue]);
                //MQConsumers::instance()->MQRoute($even,$this->queue);
            }
        }catch (\Exception $e){
            $error = $e->getMessage();
            Tools::SafeEcho("Consumers Error [$error]","#");
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
        $this->_stop($worker);
    }

    /**
     * 停止
     * @param $worker
     * @param null $msg
     * @param bool $exit
     */
    private function _stop($worker,$msg = null,$exit = true){
        if($this->timer){
            Timer::del($this->timer);
        }
        $this->queue = null;
        $this->channel = null;
        ($this->connection instanceof \AMQPConnection) ? $this->connection->disconnect() : $this->connection = null;
        $message = $msg ? "Rabbit Server Stop [{$msg}]" : "Rabbit Server Stop ";
        Tools::SafeEcho($message,"# : {$worker->workerId}|{$worker->id}");
        if($exit){
            exit('Rabbit Server'.PHP_EOL);
        }
    }

    /**
     * 检查
     * @param $worker
     */
    private function _checker($worker){
        if(!$this->config) {
            $this->_stop($worker,'CONFIG');
        }

        try{
            $this->serviceObj = call_user_func([$this->service,'::instance']);
        }catch (\Exception $e){
            try{
                $this->serviceObj = new $this->service;
            }catch (\Exception $e){
                $this->_stop($worker,$e->getMessage());
            }
        }
        if(!method_exists($this->serviceObj,$this->action)){
            $this->_stop($worker,'Function Not Found');
        }
    }
}