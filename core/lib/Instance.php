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
 * 增加 GC容器设置 常驻单例
 *  1.常驻单例，一旦实例保存，不会被GC，仅在进程退出时消亡
 *  2.通过 permanent()方法实例，超出常驻容器抛出 Exception
 *  3.permanent()方法前先 setCapacity(N)声明容器容量，否则会抛出 Exception
 *
 *  注.
 *      1.以上所说的内存释放在PHP GC前提下实现
 *      2.permanent后的实例与instance后的实例存放在不同的容器中，区分管理
 *      3.permanent操作尽量在主进程或者业务流程前声明，在业务中尽量仅做使用操作
 *
 * Class Instance
 * @package core\lib
 */
abstract class Instance{

    /**
     * @var Output
     */
    private $_output;
    /**
     * @var Result
     */
    private $_result;

    protected $_config         = [];

    private static $_instances = [];    # 单例容器 队列模型
    private static $_i_capacity= 0;     # 单例容器 容量
    private static $_use_count = 0;     # 已占用的数量

    private static $_permanents= [];    # 常驻单例容器
    private static $_p_capacity= 10;    # 常驻单例 容量
    private static $_per_count = 0;     # 已常驻的数量

    protected static $_time    = 0;     # 当前时间
    protected static $_class   = null;  # 唤起的类名


    /**
     * Service constructor.
     * @param $loadConfig
     */
    public function __construct($loadConfig = false) {
        if($loadConfig){
            $this->_loadConfig();
        }
        $this->_initConfig();
        $this->_init();
    }

    /**
     * 私有配置
     */
    protected function _loadConfig(){
        $classArr = explode('\\',self::$_class);
        $modeName = $classArr[1];
        $configPath = API_PATH . '/' . $modeName . '/configs.php';
        if(file_exists($configPath)){
            // todo 这里的配置加载并没有做隔离，载入config文件时，会整体合并覆盖Config类中的元素
            Config::load($configPath);
        }
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

    # -------------------------------------- 常驻单例

    /**
     * 常驻实例
     *
     *  1.常驻实例与普通实例不同，用于特殊情况的实例化，如 主进程加载
     *  2.常驻实例的容器与普通实例的容器不一样，需分别管理
     *  3.常驻实例无法清洗，只能通过业务逻辑的调整
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
            return self::$_permanents[self::$_class] = new self::$_class($loadConfig);
        }
        # 更新旧容器内部时间属性
        self::now();
        self::$_per_count ++;
        return self::$_permanents[self::$_class];
    }

    /**
     * 使用常驻实例
     * @return static
     * @throws \Exception
     */
    final public static function usePermanent(){
        self::$_class = get_called_class();
        # 容器中不存在
        if (!isset(self::$_permanents[self::$_class]) or !self::$_permanents[self::$_class] instanceof Instance) {
            $class = self::$_class;
            throw new \Exception("permanent service not ready | class:{$class}");
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

    # -------------------------------------- 普通单例

    /**
     * 单例模式
     *
     *  对象会存入单例容器，随着进程而保持，不会被PHP GC主动回收
     *
     * @param bool $loadConfig
     * @return static
     */
    final public static function instance($loadConfig = false) {
        self::$_class = get_called_class();
        # 如果要加载配置
        if($loadConfig){
            self::GC();
            return self::$_instances[self::$_class] = new self::$_class($loadConfig);
        }
        # 容器中不存在
        if (!isset(self::$_instances[self::$_class]) or !self::$_instances[self::$_class] instanceof Instance) {
            self::GC();
            return self::$_instances[self::$_class] = new self::$_class($loadConfig);
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
     * @param bool $config
     * @return static
     */
    final public static function factory($config = false) {
        self::$_class = get_called_class();
        return new self::$_class($config);
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