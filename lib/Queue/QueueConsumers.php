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

    protected static $_name;
    /**
     * @var array
     *
     *  [
     *      'service'     => 'Mine\Queue\QueueRoute',
     *      'event_limit' => 20000,
     *      'interval'    => 0.01
     *  ]
     */
    protected $config = [];

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

    /**
     * @var QueueRoute
     */
    private $route       = null;
    private $event_limit = 0;
    private $event_count = 0;
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
     * @var string
     */
    public $_log_path = null;

    /**
     * QueueConsumers constructor.
     * @param string $name
     */
    public function __construct(string $name = null) {
        parent::__construct();
        if($name !== null){
            self::setName($name);
        }
        $this->setName(self::getName() ? self::getName() : 'queue_server');
        $this->_init();
    }

    /**
     * config init
     */
    protected function _init(){
        Config::init();
        $config            = Config::get(Define::CONFIG_QUEUE);
        $this->config      = isset($config[$this->getName()]) ? $config[$this->getName()] : $this->config;
        $this->route       = isset($this->config['route']) ? $this->config['route'] : null;
        $this->event_limit = isset($this->config['event_limit']) ? (int)$this->config['event_limit'] : $this->event_limit;
        $this->interval    = isset($this->config['interval']) ? (float)$this->config['interval'] : $this->interval;
        $this->_route();
    }

    /**
     * 检查路由
     */
    protected function _route(){
        if(!is_subclass_of($this->route, QueueRoute::class)){
            $this->_exit('Queue Route Illegal');
        }
        try {
            $this->route = call_user_func([$this->route, 'instance']);
            if(!$this->route->getExchangeName()){
                $this->_exit('Queue Route Exception [exchange_name]');
            }
            if(!$this->route->getExchangeType()){
                $this->_exit('Queue Route Exception [exchange_type]');
            }
            if(!$this->route->getQueueName()){
                $this->_exit('Queue Route Exception [queue_name]');
            }
            return;
        }catch(\Exception $exception){
            $this->_exit("Queue Route Exception [{$exception->getMessage()}]", $exception->getCode());
        }
        $this->_exit('Queue Route Exception [instanceof]');
    }

    /**
     * 退出
     * @param string $error
     * @param string $code
     */
    protected function _exit(string $error, string $code = '500'){
        static::safeEcho(" -----------------------<w> ERROR </w>----------------------------- \r\n");
        static::safeEcho(' > <w>message</w>:' . " {$error} \r\n");
        static::safeEcho(' > <w>code   </w>:' . " {$code} \r\n");
        if(!$this->route){
            static::safeEcho(' > <w>route  </w>:' . " {$this->route} \r\n");
        }
        static::safeEcho(" -----------------------<w> ERROR </w>----------------------------- \r\n");
        exit;
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

    public static function setName(string $name){
        self::$_name = $name;
    }
    public static function getName() : string {
        return self::$_name;
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
        Tools::SafeEcho("Rabbit Server Start","# : {$worker->id}");
        $this->client = QueueBaseLib::instance();
        try{
            $this->_queue = $this->client->createQueue(
                $this->route->getExchangeName(),
                $this->route->getExchangeType(),
                $this->route->getQueueName()
            );
            if($this->_queue === false){
                $this->_exit($this->client->getException()->getMessage(),$this->client->getException()->getCode());
            }
            if($this->route->getChannelCount() > 0){
                $this->_queue->getChannel()->qos(null,$this->route->getChannelCount());
            }
            $this->timer = Timer::add($this->interval, function(){
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
            if($this->route->verify($this->getEven()->getBody())){
                try {
                    $res = call_user_func(
                        [$this->route, $this->route->getMethod()],
                        $this->getEven(), $this->getQueue()
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