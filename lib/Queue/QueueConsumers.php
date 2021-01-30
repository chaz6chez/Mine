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
use Mine\Helper\Exception;
use Mine\Helper\Tools;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
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
     * @var AMQPMessage
     */
    protected $_message;
    /**
     * @var AMQPChannel
     */
    protected $_channel;

    /**
     * @var QueueLib
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
     * @param string|null $name
     * @param bool $check_connection
     */
    public function __construct(string $name = null, bool $check_connection = true) {
        parent::__construct();
        if($name !== null){
            self::setName($name);
        }
        $this->name = self::getName() ? self::getName() : 'queueServer';
        $this->_init($check_connection);
    }

    /**
     * config init
     * @param bool $connection
     */
    protected function _init(bool $connection = true){
        Config::init();
        $config            = Config::get(Define::CONFIG_QUEUE);
        $this->config      = isset($config[$this->name]) ? $config[$this->name] : $this->config;
        $this->route       = isset($this->config['route']) ? $this->config['route'] : null;
        $this->event_limit = isset($this->config['event_limit']) ? (int)$this->config['event_limit'] : $this->event_limit;
        $this->interval    = isset($this->config['interval']) ? (float)$this->config['interval'] : $this->interval;
        $this->_route();
        if($connection){
            $this->_connection();
        }
    }

    /**
     * 检查连接
     */
    protected function _connection(){
        try {
            static::safeEcho(" > <w>Connection checking ...</w> \r\n");
            $client = QueueLib::factory();
            if(!$client->connection()->isConnected()){
                $this->_exit('Queue Server Connection Failed');
            }
            $client->closeConnection();
            static::safeEcho(" > <w>Connection succeeded ...</w> \r\n");
        }catch(\Exception $exception){
            $this->_exit('Queue Server Connection Failed : ' . $exception->getMessage(), $exception->getCode());
        }
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
        static::safeEcho(" ----------------------- ERROR ----------------------------- \r\n");
        static::safeEcho(' > <w>message</w>:' . " {$error} \r\n");
        static::safeEcho(' > <w>code   </w>:' . " {$code} \r\n");
        if(!$this->route){
            static::safeEcho(' > <w>route  </w>:' . " {$this->route} \r\n");
        }
        static::safeEcho(" ----------------------- ERROR ----------------------------- \r\n");
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
            $this->getMessage()->get('channel')->basic_ack($this->getMessage()->get('delivery_tag'));
        }catch(\Exception $exception){
            $this->_log($exception, 'ACK',Define::CONFIG_QUEUE . '_ack');
        }
    }
    protected function _nack($requeue = true){
        try {
            $this->getMessage()->get('channel')->basic_nack($this->getMessage()->get('delivery_tag'),false, $requeue);
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
    public function getMessage(){
        return $this->_message;
    }
    public function getChannel(){
        return $this->_channel;
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
        Tools::SafeEcho("Rabbit Server Start",$worker->id);
        $this->client = QueueLib::instance();
        try{
            $this->client->_exchange_name = $this->route->getExchangeName();
            $this->client->_exchange_type = $this->route->getExchangeType();
            $this->client->_queue_name    = $this->route->getQueueName();
            $this->client->queue();
            $this->_channel               = $this->client->channel();
            if($this->route->getChannelCount() > 0){
                $this->getChannel()->basic_qos(null,$this->route->getChannelCount(),true);
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
            $this->_message = $this->getChannel()->basic_get($this->client->_queue_name);
        }catch (\Exception $exception){
            Tools::SafeEcho("Consumers Error : {$exception->getMessage()}[{$exception->getCode()}]",$this->id);
            return;
        }
        if($this->getMessage()){
            if($this->route->verify($this->getMessage()->getBody())){
                try {
                    $res = call_user_func(
                        [$this->route, $this->route->getMethod()],
                        $this->client
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
                }catch(Exception $exception){
                    $this->_nack();
                    return;
                }catch(\Exception $exception){
                    $this->_log($exception,'SERVICE EXCEPTION');
                    $this->_nack();
                    return;
                }
                $this->_ack();
                return;
            }
            # 非路由消息日志保存
            $this->_log($this->getMessage()->getBody(), 'NOT ROUTE',Define::CONFIG_QUEUE . '_route');
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
        if($this->client instanceof QueueLib){
            $this->client->closeChannel();
            $this->client->closeConnection();
        }
        Tools::SafeEcho("Rabbit Server Stop ",$worker->id);
    }
}