<?php
/**
 * Created by PhpStorm.
 * User: zm
 * Date: 2019/1/30
 * Time: 14:42
 */

namespace core\crontab;

use core\lib\Autoload;
use core\lib\Config;
use Cron\CronExpression;
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
        $config = Config::get('task');
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
            cli_echo_debug("Task Server Config Error", "# : {$worker->workerId}|{$worker->id}");
            $this->stop();
        }
        if(!CronExpression::isValidExpression($this->cronExpression)){
            cli_echo_debug("Cron-expression Error", "# : {$worker->workerId}|{$worker->id}");
            $this->stop();
        }
        try{
            $this->serviceObj = new $this->service;
        }catch (\Exception $e){
            cli_echo_debug("Task Server {$e->getMessage()}", "# : {$worker->workerId}|{$worker->id}");
            $this->stop();
        }
        if(
            !method_exists($this->serviceObj,$this->upperFunc)
        ){
            cli_echo_debug("Upper-half Function Not Found", "# : {$worker->workerId}|{$worker->id}");
            $this->stop();
        }
        if(
            $this->doubleLine and
            !method_exists($this->serviceObj,$this->lowerFunc)
        ){
            cli_echo_debug("Lower-half Function Not Found", "# : {$worker->workerId}|{$worker->id}");
            $this->stop();
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
        $this->_Stop($worker);
    }

    /**
     * 运行
     * @param $worker
     */
    private function _Run($worker){
        $this->corn = CronExpression::factory($this->cronExpression);
        cli_echo_debug("Task Server Start","# : {$worker->workerId}|{$worker->id}");
        $this->_Cron();

    }

    /**
     * 停止
     * @param $worker
     */
    private function _Stop($worker){
        if($this->upperHalf){
            Timer::del($this->upperHalf);
        }
        if($this->lowerHalf){
            Timer::del($this->lowerHalf);
        }
        cli_echo_debug("Task Server Stop","# : {$worker->workerId}|{$worker->id}");
    }

    /**
     * 上半区
     */
    private function _Cron(){
        # 每分钟执行一次
        $this->upperHalf = Timer::add(60,function(){
//            if($this->corn->isDue()){
                $res = call_user_func([$this->serviceObj,$this->upperFunc]);
                if(!$res[0]){ # 失败记录日志
                    log_add($res[1],"TaskService_{$this->name}",__METHOD__);
                }
                if($this->doubleLine){
                    $this->_CronLowerHalf($res[1]);
                }
//            }
            cli_echo_debug($this->corn->getPreviousRunDate()->getTimestamp());
            cli_echo_debug($this->corn->getNextRunDate()->getTimestamp());
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
                $res = call_user_func([$this->serviceObj,$this->lowerFunc],$param);
            }else{
                $res = call_user_func([$this->serviceObj,$this->lowerFunc]);
            }
            if(!$res[0]){ # 失败记录日志
                log_add($res[1],"TaskService_{$this->name}",__METHOD__);
            }
        },[$param],false);
    }
}