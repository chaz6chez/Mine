<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/19           #
# -------------------------- #
namespace core;

use core\lib\Config;
use core\lib\Autoload;
use core\lib\Route;

class App{

    private $_allowedRoute   = [];  # 授权的路由
    private $_forbiddenRoute = [];  # 拒绝的路由
    private $_defaultPath    = '';  # 默认路径

    /**
     * 加载
     */
    public function init(){
        //引入composer自动载入
        $this->_init();
        //载入公共方法
        $this->_funcInit();
        //设置头
        $this->_setHeader();
        //载入配置
        $this->_configInit();
        //自动载入函数
        $this->_setAutoload();
        //设置路由
        $this->_setRoute();
    }

    /**
     * 载入初始化
     */
    private function _init(){
        require_once ROOT_PATH . '/vendor/autoload.php';
    }

    /**
     * 载入系统配置文件[公共]
     */
    private function _configInit(){
        Config::init();
    }

    /**
     * 载入公共方法
     */
    private function _funcInit(){
        require_once COMMON_PATH . '/functions.php';
    }

    /**
     * 头
     */
    private function _setHeader(){
        wm_header('Content-type: text/html; charset=UTF-8');
    }
    /**
     * 自动载入(异常补充)
     */
    private function _setAutoload(){
        $autoload = new Autoload();
        $autoload->register();
    }
    /**
     * 设置路由
     */
    private function _setRoute(){
        $routeObj = new Route();
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