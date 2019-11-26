<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/19           #
# -------------------------- #
namespace Mine;

use Mine\Helper\Tools;
use Mine\Core\Autoload;
use Mine\Core\Config;
use Mine\Core\Route;

/**
 *  1._configInit() 每一次有请求时都会require Common下的config文件
 *  2._funcInit() 仅会在第一次请求时require加载公共函数，随后的请求都会直接调用进程加载好的公共函数
 *  3.请在服务启动时加载 composer autoload
 *
 * Class App
 * @package core
 */
class App{

    private $_allowedRoute   = [];  # 授权的路由
    private $_forbiddenRoute = [];  # 拒绝的路由
    private $_defaultPath    = '';  # 默认路径

    /**
     * 加载
     * @return $this
     */
    public function init(){
        //载入配置
        $this->_configInit();

        return $this;
    }

    /**
     * 运行
     */
    public function run(){
        //设置头
        $this->_setHeader();
        //设置时间
        $this->_setTime();
        //自动载入函数
        $this->_setAutoload();
        //设置路由 并执行
        $this->_setRoute();
    }

    /**
     * 载入系统配置文件[公共]
     */
    private function _configInit(){
        Config::init();
    }

    /**
     * 设置时间
     */
    private function _setTime(){
        $GLOBALS['NOW_TIME'] = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
    }

    /**
     * 设置默认头
     */
    private function _setHeader(){
        Tools::Header('Content-Type: application/json;charset=utf-8');
    }

    /**
     * 自动载入(异常补充)
     */
    private function _setAutoload(){
        $autoload = Autoload::instance();
        $autoload->register();
    }

    /**
     * 设置路由
     */
    private function _setRoute(){
        $routeObj = Route::instance();
        if($this->_defaultPath){
            $routeObj->setDefaultRoute($this->_defaultPath);
        }
        if($this->_allowedRoute){
            $routeObj->setAllowed($this->_allowedRoute);
        }
        if($this->_forbiddenRoute){
            $routeObj->setForbidden($this->_forbiddenRoute);
        }
        $routeObj->init()->run();
    }

    /**
     * 设置允许的路由(在init方法之前调用有效)
     * @param array $allowed
     */
    public function setAllowedRoute(array $allowed){
        $this->_allowedRoute = $allowed;
    }

    /**
     * 设置默认路径
     * @param string $path
     */
    public function setDefaultRoute(string $path){
        $this->_defaultPath = $path;
    }

    /**
     * 设置被拒绝的路由(在init方法之前调用有效)
     * @param array $forbidden
     */
    public function setForbiddenRoute(array $forbidden){
        $this->_forbiddenRoute = $forbidden;
    }
}