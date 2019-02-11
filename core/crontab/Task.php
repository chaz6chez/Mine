<?php
/**
 * Created by PhpStorm.
 * User: zm
 * Date: 2019/1/30
 * Time: 14:42
 */

namespace core\crontab;

use Api\V1\Service\CtocOrder;
use core\lib\Config;
use \Workerman\Worker;
use \Workerman\Lib\Timer;

class Task extends Worker{
    public $configPath   = null;
    public $functionPath = null;

    /**
     * @var Timer
     */
    protected $timer = null;

    /**
     * Task constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->name = 'Task';
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
        cli_echo_debug("Task Server Start","# : {$worker->workerId}|{$worker->id}");

        // 每30秒执行一次，清理过期订单
        $this->timer = Timer::add(30,function(){
            CtocOrder::instance()->cancelExpireOrder();
        });
    }

    /**
     * stop
     * @param Worker $worker
     */
    public function onWorkerStop($worker){
        Timer::del($this->timer);
        cli_echo_debug("Task Server Stop","# : {$worker->workerId}|{$worker->id}");
    }
}