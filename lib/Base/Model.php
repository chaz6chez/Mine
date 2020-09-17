<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/17          #
# -------------------------- #
namespace Mine\Base;

use Mine\Db\Connection;
use Mine\Db\Db;

/**
 * 模型类是一个享元模式的单例容器
 * 与 Instance类相同
 *  1.
 *
 * Class Model
 * @package core\base
 */
class Model {
    const STATUS_IDLE = 0;
    const STATUS_BUSY = 1;

    protected $_table;
    protected $_dbName;
    protected $_status   = self::STATUS_IDLE;
    protected $_dbMaster = [];
    protected $_dbSlave  = [];
    protected $_slave    = false;
    private static $_instances = [];

    public function setSlave(bool $key){
        $this->_slave = $key;
        return $this;
    }

    public function getSlave() : bool {
        return $this->_slave;
    }

    /**
     * Model constructor.
     */
    final private function __construct() {
        $this->_init();
    }

    protected function _init() {}

    /**
     * 查看已实例的类
     * @param string $className
     * @return array|mixed|null
     */
    final public function getInstances($className = ''){
        if($className){
            return isset(self::$_instances[$className]) ? self::$_instances[$className] : null;
        }
        return self::$_instances;
    }

    /**
     * 容器 GC
     * @param int $limit
     */
    final private static function GC($limit = 10){
        # 判断容器容量
        if(!$limit){
            return;
        }
        $count = count(self::$_instances);
        if($count > 0){
            if(($redundant = $count - (int)$limit) > 0){
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
     *  1.对象会存入单例容器，随着进程而保持，不会被PHP GC主动回收
     *  2.与Instance类不同的是，每次调用都会执行队列更新
     *
     * @return static
     */
    final public static function instance() {
        $class = get_called_class();
        # 容器中不存在 新增
        if (!isset(self::$_instances[$class]) or !self::$_instances[$class] instanceof Model) {
            self::GC();
            return self::$_instances[$class] = new $class();
        }
        # 容器中存在 更新位置
        else{
            $obj = self::$_instances[$class];
            unset(self::$_instances[$class]);
            self::$_instances[$class] = $obj;
        }

        return self::$_instances[$class];
    }

    /**
     * 工厂模式
     *
     *  对象不会存入单例容器，随着方法体执行完毕而被PHP GC主动回收
     *
     * @return static
     */
    final public static function factory() {
        $class = get_called_class();
        return new $class();
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
     *  清除后交给PHP GC进行回收
     *
     */
    final public function instanceRemove(){
        $class = get_called_class();
        unset(self::$_instances[$class]);
    }

    /**
     * 获得数据库组件
     * @return Db
     */
    public function db() {
        return Db::instance();
    }

    /**
     * 获得主数据库连接
     * @param string $name
     * @return Connection|bool
     */
    public function dbName($name = 'default') {
        if ($name == 'default') {
            $dbName = !$this->_dbName ? $name : $this->_dbName;
        } else {
            $dbName = $name;
        }
        if($this->getSlave()){
            if (
                !isset($this->_dbSlave[$dbName]) or
                !$this->_dbSlave[$dbName] instanceof Connection
            ) {
                $res = $this->_dbSlave[$dbName] = $this->db()->dbNameSlave($dbName);
            }else{
                $res = $this->_dbSlave[$dbName];
            }
        }else{
            if (
                !isset($this->_dbMaster[$dbName]) or
                !$this->_dbMaster[$dbName] instanceof Connection
            ) {
                $res = $this->_dbMaster[$dbName] = $this->db()->dbName($dbName);
            }else{
                $res = $this->_dbMaster[$dbName];
            }
        }

        return $res;
    }

    /**
     * 获取表名
     * @param string $name
     * @return string
     */
    public function tb($name = '') {
        if ($name === '') {
            return $this->_table;
        }
        $v = "_table_{$name}";
        return $this->$v;
    }

    /**
     * 应用额外选项,其实就是通过数组的方式调用方法
     * @param $db
     * @param $options
     */
    protected function _applyOptions($db, $options) {
        foreach ($options as $m => $opt) {
            if (method_exists($db, $m)) {
                call_user_func_array([$db, $m], $opt);
            }
        }
    }

}
