<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/20            #
# -------------------------- #
namespace core\lib;

/**
 * 常驻单例容器
 *
 *  1.常驻单例，一旦实例保存，不会被GC，仅在进程退出时消亡
 *  2.通过 permanent()方法实例，超出常驻容器抛出 Exception
 *  3.permanent()方法前先 setCapacity(N)声明容器容量，否则会抛出 Exception
 *
 *  注.
 *      1.permanent后的实例与instance后的实例存放在不同的容器中，区分管理
 *      2.permanent操作尽量在主进程或者业务流程前声明，在业务中尽量仅做使用操作
 *
 * Class Permanent
 * @package core\lib
 */
abstract class Permanent{

    /**
     * @var Output
     */
    private $_output;
    /**
     * @var Result
     */
    private $_result;

    protected $_config         = [];

    private static $_permanents= [];    # 常驻单例容器
    private static $_p_capacity= 10;    # 常驻单例 容量
    private static $_per_count = 0;     # 已常驻的数量

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
     * 常驻单例
     *
     * @param bool $loadConfig
     * @return static
     * @throws \Exception
     */
    final public static function permanent($loadConfig = false){
        self::$_class = get_called_class();
        # 判断容器
        if(self::$_per_count + 1 > self::$_p_capacity){
            $self = self::$_per_count;
            $num = self::$_p_capacity;
            throw new \Exception("permanent service failed | all:{$num} used:{$self}");
        }
        # 容器中不存在
        if (!isset(self::$_permanents[self::$_class]) or !self::$_permanents[self::$_class] instanceof Instance) {
            self::$_per_count ++;
            return self::$_permanents[self::$_class] = new self::$_class($loadConfig);
        }
        # 更新旧容器内部时间属性
        self::now();
        return self::$_permanents[self::$_class];
    }

    /**
     * 设置常驻实例容器
     * @param int $limit
     * @throws \Exception
     */
    final public static function setCapacity(int $limit){
        if(!$limit){
            throw new \Exception('Incorrect capacity format');
        }
        if(!$limit < self::$_per_count){
            throw new \Exception('Low capacity');
        }
        self::$_p_capacity = $limit;
    }

    /**
     * 查看已实例的常驻类
     * @param string $className
     * @return array|mixed|null
     */
    final static public function getPermanents($className = ''){
        if($className){
            return isset(self::$_permanents[$className]) ? self::$_permanents[$className] : null;
        }
        return self::$_permanents;
    }

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
     * 获取结果
     * @param $result
     * @return Result
     */
    protected function result($result) {
        $this->_result = new Result($result);
        $this->_result->setPattern('arr');
        return $this->_result;
    }

    /**
     * 获取输出器对象
     * @param string $pattern
     * @return array|Output|mixed
     */
    protected function output($pattern = 'arr') {
        if (!$this->_output or !$this->_output instanceof Output) {
            $this->_output = new Output();
        }
        if(is_string($pattern)){
            $this->_output->setPattern($pattern);
        }
        if (is_array($pattern)) {
            $this->_output->setPattern('arr');
            if (isset($pattern['errCode']) && isset($pattern['message']) && isset($pattern['data'])) {
                return $this->_output->output($pattern['errCode'], $pattern['message'], $pattern['data']);
            } else {
                return $this->_output->success($pattern);
            }
        }
        return $this->_output;
    }
}