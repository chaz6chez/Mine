<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/19            #
# -------------------------- #
namespace Mine\Core;


use Mine\Helper\Arr;
use Mine\Helper\Tools;

/**
 * 简单的路由功能
 *  1.allowed
 *  2.forbidden
 *  3.PATH_INFO
 *  4.default route
 *  5.base path
 *
 * Class Route
 * @package core\lib
 */
final class Route{

    public $_base    = 'Api';
    public $_path    = [];
    public $_mode    = 'Index';
    public $_ctrl    = 'Index';
    public $_action  = 'Index';
    private $_allowed    = [];
    private $_forbidden  = ['Common'];
    private static $_instance;
    public static $_API_MODULE = 'unknown';

    /**
     * Route constructor.
     */
    final public function __construct(){}

    /**
     * 单例
     * @return Route
     */
    final public static function instance(){
        if(!self::$_instance or !self::$_instance instanceof Route){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * todo Route 可参考hands-off类型的框架的Route类拓展，如pinatra/framework
     */
    public function init(){
        $this->_path = !empty($_SERVER['PATH_INFO']) ? explode('/', $_SERVER['PATH_INFO']) : [];
        $this->_mode = (isset($this->_path[1]) and $this->_path[1]) ? $this->_path[1] : $this->_mode;
        $this->_ctrl = (isset($this->_path[2]) and $this->_path[2]) ? $this->_path[2] : $this->_ctrl;
        $this->_action = (isset($this->_path[3]) and $this->_path[3]) ? $this->_path[3] : $this->_action;
        return $this;
    }

    /**
     * 设置项目基
     * @param $base
     */
    public function setBase($base){
        if(is_string($base) and $base){
            $this->_base = $base;
        }
    }

    /**
     * 设置默认路径
     * @param string $path 格式：module/controller/action
     */
    public function setDefaultRoute(string $path){
        $path = explode('/',$path);
        if(count($path) >= 3){
            $this->_mode = ucfirst($path[0]);
            $this->_ctrl = ucfirst($path[1]);
            $this->_action = $path[2];
        }
    }

    /**
     * 设置允许的路由
     * @param array $allowed
     * @param bool $cover 是否覆盖
     * @return array
     */
    public function setAllowed(array $allowed,$cover = false){
        if($cover){
            return $this->_allowed = $allowed;
        }
        return $this->_allowed = Arr::merge($this->_allowed,$allowed);
    }

    /**
     * 设置禁止的路由
     * @param array $forbidden
     * @param bool $cover 是否覆盖
     * @return array
     */
    public function setForbidden(array $forbidden,$cover = false){
        if($cover){
            return $this->_forbidden = $forbidden;
        }
        return $this->_forbidden = Arr::merge($this->_forbidden,$forbidden);
    }

    /**
     * 允许的路由与禁止的路由区别在于返回信息
     * 允许路由之外返回404错误
     * 禁止路由之内返回403错误
     */
    public function run(){
        $this->_mode = ucfirst($this->_mode);

        if($this->_forbidden){
            if(in_array($this->_mode,$this->_forbidden)){
                $this->clean();
                Tools::Http403("{$this->_mode} Forbidden");
            }
        }
        if($this->_allowed){
            if(!in_array($this->_mode,$this->_allowed)){
                $this->clean();
                Tools::Http404("{$this->_mode} Not Found");
            }
        }

        $this->_ctrl = ucfirst($this->_ctrl);
        # \项目基目录\模块目录\控制器目录\文件名
        $c = "\\{$this->_base}\\{$this->_mode}\Controller\\{$this->_ctrl}";

        $preg = preg_match_all('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/',$this->_ctrl);
        if($preg > 1){
            Tools::Http404("{$this->_ctrl} Not Found");
        }

        $GLOBALS['API_MODULE'] = Tools::CamelToLower($this->_mode);
        self::$_API_MODULE = $GLOBALS['API_MODULE'];

        $obj = new $c();
        $methodName = $this->_action;
        $obj->$methodName();
    }

    public function clean(){
        $this->_mode    = 'Index';
        $this->_ctrl    = 'Index';
        $this->_action  = 'Index';
    }
}