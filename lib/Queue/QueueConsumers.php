<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/23            #
# -------------------------- #
namespace Mine\Queue;

use Mine\Core\Config;
use Mine\Core\Response;
use Mine\Definition\Define;
use Mine\Helper\Tools;
use Workerman\Lib\Timer;
use Workerman\Worker;

class QueueConsumers extends Worker{

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
    protected $_even;
    protected $_queue;
    /**
     * @var QueueBaseLib
     */
    protected $client = null;

    /**
     * @var Timer
     */
    protected $timer = null;

    private $eventLimit  = 20000;
    private $eventCount  = 0;
    private $interval    = 0.01;
    /**
     * @var callable
     */
    public $_error_callback = null;
    /**
     * @var callable
     */
    public $_success_callback = null;
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

    public function getEven() : \AMQPEnvelope{
        return $this->_even;
    }

    public function getQueue() : \AMQPQueue{
        return $this->_queue;
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
     * start
     * @param Worker $worker
     */
    public function onWorkerStart($worker){
        $this->confInit();
        Tools::SafeEcho("Rabbit Server Start","# : {$worker->workerId}|{$worker->id}");
        $this->_checker($worker);
        $this->client = QueueBaseLib::instance();
        try{
            $this->_queue = $this->client->createQueue();
            $this->timer = Timer::add($this->interval,function(){
                $this->restart();
                $this->queueConsume(); # 非阻塞调用
            });
        }catch (\Exception $e){
            $error = $e->getMessage();
            $this->_stop($worker,$error);
            return;
        }
        return;
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
     */
    protected function queueConsume(){
        try{
            $this->_even = $this->getQueue()->get();
        }catch (\Exception $exception){
            Tools::SafeEcho("Consumers Error : {$exception->getMessage()}[{$exception->getCode()}]","#");
            return;
        }
        if($this->getEven()){
            try {
                $res = call_user_func(
                    [$this->serviceObj,$this->action],
                    [$this->getEven(), $this->getQueue()]
                );
                if($res instanceof Response){
                    if($res->hasError() and $this->_error_callback){
                        call_user_func($this->_error_callback, $this);
                        return;
                    }
                    if($this->_success_callback){
                        call_user_func($this->_success_callback, $this);
                    }
                }
                $this->getQueue()->ack($this->getEven()->getDeliveryTag());
            }catch(\Exception $exception){
                $this->getQueue()->nack($this->getEven()->getDeliveryTag());
            }
        }
        return;
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
        $this->client->closeChannel();
        $this->client->closeConnection();
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