<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/20            #
# -------------------------- #
namespace core\lib;

/**
 * 单例容器
 *
 *  1.使用享元模式开发，所有子类公用一个容器，共同管理
 *  2.内部实现计数 GC，自动控制其单例容器释放内存
 *  3.instanceClean()、instanceRemove()方法可主动释放内存
 *  4.getInstances()方法可获取当前容器情况
 *
 *  注.
 *      1.以上所说的内存释放在PHP GC前提下实现
 *
 * Class Instance
 * @package core\lib
 */
abstract class Instance{

    protected $_config         = [];

    private static $_instances = [];    # 单例容器 队列模型
    private static $_i_capacity= 0;     # 单例容器 容量
    private static $_use_count = 0;     # 已占用的数量

    protected static $_time    = 0;     # 当前时间
    protected static $_class   = null;  # 唤起的类名


    /**
     * Service constructor.
     */
    public function __construct() {
        $this->_initConfig();
        $this->_init();
    }

    /**
     * 载入配置内容
     */
    abstract protected function _initConfig();

    /**
     * 读取配置
     * @param null $key
     * @param null $default
     * @return array|mixed|null
     */
    public function getConfig($key = null, $default = null) {
        if(!$key){
            return $this->_config;
        }
        return array_key_exists($key, $this->_config) ? $this->_config[$key] : $default;
    }

    /**
     * 动态改变设置
     * @param $key
     * @param $value
     */
    public function setConfig($key, $value) {
        $this->_config[$key] = $value;
    }

    /**
     * 获取时间
     * @return int|mixed
     */
    protected static function now(){
        return self::$_time = isset($GLOBALS['NOW_TIME']) ? $GLOBALS['NOW_TIME'] : time();
    }

    /**
     * 模块初始化配置,方法中应确保实例多次调用不存参数副作用
     */
    protected function _init(){
        self::now();
    }

    /**
     * 容器 GC
     * @param int $limit
     */
    final private static function GC($limit = 10){
        if(defined('INSTANCES_LIMIT') and INSTANCES_LIMIT){
            $limit = INSTANCES_LIMIT;
        }
        self::$_i_capacity = $limit;
        # 判断容器容量
        $count = count(self::$_instances);

        if($count > 0){
            self::$_use_count = $count;
            if(($redundant = $count - (int)$limit) > 0){
                self::$_use_count = $limit;
                # 溢出的对象出队 等待PHP GC
                do{
                    array_shift(self::$_instances);
                    $redundant --;
                }while($redundant > 0);
            }
        }
    }

    /**
     * 单例模式
     *
     *  对象会存入单例容器，随着进程而保持，不会被PHP GC主动回收
     *
     * @return static
     */
    final public static function instance() {
        self::$_class = get_called_class();
        # 容器中不存在
        if (!isset(self::$_instances[self::$_class]) or !self::$_instances[self::$_class] instanceof Instance) {
            self::GC();
            return self::$_instances[self::$_class] = new self::$_class();
        }
        # 更新旧容器内部时间属性
        self::now();
        return self::$_instances[self::$_class];
    }

    /**
     * 工厂模式
     *
     *  对象不会存入单例容器，随着方法体执行完毕而被PHP GC主动回收
     *
     * @return static
     */
    final public static function factory() {
        self::$_class = get_called_class();
        return new self::$_class();
    }

    /**
     * 单例容器全清
     *
     *  清除后交给PHP GC进行回收
     *
     */
    final public function instanceClean(){
        self::$_instances = [];
    }

    /**
     * 单例容器清除
     *
     *  重载的对象不会更新读取配置
     *
     * @param bool $reload 是否重载
     */
    final public function instanceRemove($reload = false){
        $class = get_called_class();
        unset(self::$_instances[$class]);
        if($reload){
            self::$_class = $class;
            self::GC();
            self::$_instances[$class] = new $class();
        }
    }

    /**
     * 查看已实例的类
     * @param string $className
     * @return array|mixed|null
     */
    final static public function getInstances($className = ''){
        if($className){
            return isset(self::$_instances[$className]) ? self::$_instances[$className] : null;
        }
        return self::$_instances;
    }
}