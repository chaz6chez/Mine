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
     */
    protected $config = [
        'service'     => 'Mine\Queue\QueueRoute',
        'event_limit' => 20000,
        'interval'    => 0.01
    ];

    /**
     * @var \AMQPEnvelope
     */
    protected $_even;
    /**
     * @var \AMQPQueue
     */
    protected $_queue;

    /**
     * @var QueueBaseLib
     */
    protected $client = null;

    /**
     * @var Timer
     */
    protected $timer = null;

    private $service     = null;
    private $event_limit = 0;
    private $event_count = 0;
    private $interval    = 0.01;
    /**
     * @var QueueBody
     */
    private $body;
    /**
     * @var callable
     */
    public $_error_callback = null;
    /**
     * @var callable
     */
    public $_success_callback = null;

    /**
     * @var string
     */
    public $_log_path = null;

    /**
     * MQServer constructor.
     */
    public function __construct() {
        parent::__construct();
        if($this->name === 'none' or !$this->name){
            $this->setName('queue_server');
        }
    }

    /**
     * config init
     */
    protected function _init(){
        Config::init();
        $config = Config::get(Define::CONFIG_QUEUE);
        $this->config = isset($config[$this->getName()]) ? $config[$this->getName()] : $this->config;
        $this->service = isset($this->config['service']) ? $this->config['service'] : null;
        $this->event_limit = isset($this->config['event_limit']) ? (int)$this->config['event_limit'] : $this->event_limit;
        $this->interval = isset($this->config['interval']) ? (float)$this->config['interval'] : $this->interval;
        $this->body = !$this->body instanceof QueueBody ? QueueBody::factory() : $this->body;
    }

    /**
     * 计数重启
     */
    protected function _restart(){
        if(
            $this->event_limit !== 0 and
            ++$this->event_count > $this->event_limit
        ){
            $this->event_count = 0;
            Worker::stopAll();
        }
    }

    /**
     * @param $log
     * @param $tag
     * @param string $module
     */
    protected function _log($log, $tag, $module = Define::CONFIG_QUEUE){
        if($this->_log_path){
            if($log instanceof \Exception){
                $log = "{$log->getCode()} : {$log->getMessage()}";
            }
            Tools::log($module, $log, $this->_log_path, $tag);
        }
    }

    protected function _ack(){
        try {
            $this->getQueue()->ack($this->getEven()->getDeliveryTag());
        }catch(\Exception $exception){
            $this->_log($exception, 'ACK',Define::CONFIG_QUEUE . '_ack');
        }
    }
    protected function _nack(){
        try {
            $this->getQueue()->nack($this->getEven()->getDeliveryTag());
        }catch(\Exception $exception){
            $this->_log($exception, 'NACK',Define::CONFIG_QUEUE . '_nack');
        }
    }

    public function setName(string $name){
        $this->name = $name;
    }
    public function getName() : string {
        return $this->name;
    }
    public function getEven(){
        return $this->_even;
    }
    public function getQueue(){
        return $this->_queue;
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
     * start
     * @param Worker $worker
     */
    public function onWorkerStart($worker){
        Tools::SafeEcho("Rabbit Server Start","# : {$worker->workerId}|{$worker->id}");
        $this->_init();

        $this->client = QueueBaseLib::instance();
        try{
            $this->_queue = $this->client->createQueue('ccc',$this->client::EXCHANGE_TYPE_DIRECT,'ccc');
            if($this->_queue === false){
                exit("{$this->client->getException()->getMessage()} : {$this->client->getException()->getCode()} \n");
            }
            $this->timer = Timer::add($this->interval,function(){
                $this->_restart();
                $this->queueConsume(); # 非阻塞调用
            });
        }catch (\Exception $e){
            $this->_log($e, 'WORKER START');
            return;
        }
        return;
    }

    /**
     * 队列消费
     */
    public function queueConsume(){
        try{
            $this->_even = $this->getQueue()->get();
        }catch (\Exception $exception){
            Tools::SafeEcho("Consumers Error : {$exception->getMessage()}[{$exception->getCode()}]","#");
            return;
        }
        if($this->getEven()){
            $this->body->clean(true);
            $this->body->create($this->client::decode($this->getEven()->getBody()));
            //todo
            if(class_exists($this->service) and is_subclass_of($this->service, QueueRoute::class)){
                try {
                    $serviceObj = call_user_func([$this->service, '::instance']);
                    $res = call_user_func(
                        [$serviceObj, QueueRoute::ENTRANCE],
                        [$this->getEven(), $this->getQueue()]
                    );
                    if($res instanceof Response){
                        if($res->hasError()){
                            if(is_callable($this->_error_callback)){
                                call_user_func($this->_error_callback, $this);
                            }
                        }else{
                            if(is_callable($this->_success_callback)){
                                call_user_func($this->_success_callback, $this);
                            }
                        }
                    }
                }catch(\Exception $exception){
                    $this->_log($exception,'SERVICE EXCEPTION');
                    $this->_nack();
                    return;
                }
                $this->_ack();
                return;
            }
            # 非路由消息日志保存
            $this->_log($this->getEven()->getBody(), 'NOT ROUTE',Define::CONFIG_QUEUE . '_route');
            $this->_ack();
            return;
        }
        return;
    }

    /**
     * stop
     * @param Worker $worker
     */
    public function onWorkerStop($worker){
        if($this->timer){
            Timer::del($this->timer);
        }
        if($this->client instanceof QueueConsumers){
            $this->client->closeChannel();
            $this->client->closeConnection();
        }
        Tools::SafeEcho("Rabbit Server Stop ","# : {$worker->workerId}|{$worker->id}");
    }
}