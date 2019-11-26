<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/19           #
# -------------------------- #
namespace lib\Cron;

use Cron\CronExpression;
use Mine\Core\Autoload;
use Mine\Core\Config;
use Mine\Definition\Define;
use Mine\Helper\Tools;
use \Workerman\Worker;
use \Workerman\Lib\Timer;

/**
 * Class TaskService
 * @package core\crontab
 */
class TaskService extends Worker{
    private $configPath     = null;
    private $functionsPath  = null;
    /**
     * @var array
     *
     *  WorkerName => [
     *      'service'         => 服务的组件地址 例:Api\V1\Service\Trade,
     *      'upper_func'      => 上半区执行方法 例:,
     *      'lower_func'      => 下半区执行方法,
     *      'double_line'     => 是否执行下半区,
     *      'cron_expression' => 执行命令
     * ]
     */
    protected $config     = [];
    protected $service    = null;
    protected $upperFunc  = null;
    protected $lowerFunc  = null;
    protected $doubleLine = false;
    protected $serviceObj = null;
    protected $cronExpression = '0 1 * * *'; # 每天凌晨1点运行

    /**
     * @var Timer
     */
    protected $upperHalf = null;
    /**
     * @var Timer
     */
    protected $lowerHalf = null;

    /**
     * @var CronExpression
     */
    private $corn = null;

    /**
     * Task constructor.
     */
    public function __construct() {
        $this->name = 'TaskService';
        parent::__construct();
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
     * 设置进程名称
     * @param $name
     */
    public function setName($name){
        $this->name = $name;
    }

    /**
     * 设置配置文件path
     * @param $configPath
     */
    public function setConfig($configPath){
        $this->configPath = $configPath;
    }

    /**
     * 设置公共函数path
     * @param $functionsPath
     */
    public function setFunctions($functionsPath){
        $this->functionsPath = $functionsPath;
    }

    /**
     * 设置cronExpression
     * @param $cronExpression
     */
    public function setCronExpression($cronExpression){
        $this->cronExpression = $cronExpression;
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
        $config = Config::get(Define::CONFIG_TASK);
        $this->config = isset($config[$this->name]) ? $config[$this->name] : [];
        $this->service = isset($this->config['service']) ? $this->config['service'] : null;
        $this->upperFunc = isset($this->config['upper_func']) ? $this->config['upper_func'] : null;
        $this->lowerFunc = isset($this->config['lower_func']) ? $this->config['lower_func'] : null;
        $this->doubleLine = isset($this->config['double_line']) ? $this->config['double_line'] : false;
        $this->cronExpression = isset($this->config['cron_expression']) ? $this->config['cron_expression'] : '0 1 * * *';
    }

    /**
     * function init
     */
    public function funcInit(){
        if($this->functionsPath){
            if(file_exists($this->functionsPath)){
                require_once $this->functionsPath;
            }
        }
    }

    /**
     * 设置自动加载
     */
    private function setAutoload(){
        $autoload = Autoload::instance();
        $autoload->register();
    }

    /**
     * 检查
     * @param $worker
     */
    private function checker($worker){
        if(!$this->config) {
            Tools::SafeEcho("Task Server Config Error", "# : {$worker->workerId}|{$worker->id}");
            $this->_Stop($worker);
        }
        if(!CronExpression::isValidExpression($this->cronExpression)){
            Tools::SafeEcho("Cron-expression Error", "# : {$worker->workerId}|{$worker->id}");
            $this->_Stop($worker);
        }
        try{
            $this->serviceObj = new $this->service;
        }catch (\Exception $e){
            Tools::SafeEcho("Task Server {$e->getMessage()}", "# : {$worker->workerId}|{$worker->id}");
            $this->_Stop($worker);
        }
        if(
            !method_exists($this->serviceObj,$this->upperFunc)
        ){
            Tools::SafeEcho("Upper-half Function Not Found", "# : {$worker->workerId}|{$worker->id}");
            $this->_Stop($worker);
        }
        if(
            $this->doubleLine and
            !method_exists($this->serviceObj,$this->lowerFunc)
        ){
            Tools::SafeEcho("Lower-half Function Not Found", "# : {$worker->workerId}|{$worker->id}");
            $this->_Stop($worker);
        }
    }

    /**
     * start
     * @param Worker $worker
     */
    public function onWorkerStart($worker){
        $this->funcInit();
        $this->confInit();
        $this->setAutoload();
        $this->checker($worker);
        $this->_Run($worker);
    }

    /**
     * stop
     * @param Worker $worker
     */
    public function onWorkerStop($worker){
        $this->_Stop($worker,false);
    }

    /**
     * 运行
     * @param $worker
     */
    private function _Run($worker){
        $this->corn = CronExpression::factory($this->cronExpression);
        Tools::SafeEcho("Task Server Start","# : {$worker->workerId}|{$worker->id}");
        $this->_Cron();

    }

    /**
     * 停止
     * @param $worker
     * @param bool $exit
     */
    private function _Stop($worker,$exit = true){
        if($this->upperHalf){
            Timer::del($this->upperHalf);
        }
        if($this->lowerHalf){
            Timer::del($this->lowerHalf);
        }
        Tools::SafeEcho("Task Server Stop","# : {$worker->workerId}|{$worker->id}");
        if($exit){
            exit('Task Error'.PHP_EOL);
        }
    }

    /**
     * 上半区
     */
    private function _Cron(){
        # 每分钟执行一次
        $this->upperHalf = Timer::add(60,function(){
            if($this->corn->isDue()){
                list($key, $res) = call_user_func([$this->serviceObj,$this->upperFunc]);
                if(!$key){ # 失败记录日志
                    log_add($res,"task_failed_{$this->name}",set_tag(__METHOD__, date('H:i:s')));
                }
                if($this->doubleLine){
                    $this->_CronLowerHalf($res);
                }
            }
            Tools::SafeEcho($this->corn->getPreviousRunDate());
            Tools::SafeEcho($this->corn->getNextRunDate());
        });
    }

    /**
     * 下半区
     *
     *  上半区成功后间隔一秒执行
     *
     * @param null $param
     */
    private function _CronLowerHalf($param = null){
        $this->lowerHalf = Timer::add(1,function($param){
            if($param){
                list($key, $res) = call_user_func([$this->serviceObj,$this->lowerFunc],$param);
            }else{
                list($key, $res) = call_user_func([$this->serviceObj,$this->lowerFunc]);
            }
            if(!$key){ # 失败记录日志
                log_add($res,"task_failed_{$this->name}",set_tag(__METHOD__, date('H:i:s')));
            }
        },[$param],false);
    }
}