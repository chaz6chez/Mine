<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/17          #
# -------------------------- #

namespace core\db;

use core\lib\Instance;
use core\helper\Arr;

class Connection{

    /**
     * @var Medoo
     */
    protected $_medoo;

    /**
     * 当前连接是否被激活(是否连接)
     * @var bool
     */
    protected $_active = false;

    /**
     * 数据库配置
     * @var array
     */
    protected $_config = [
        'database_type' => 'mysql',
        'server' => '',
        'username' => '',
        'password' => '',
        'database_file' => '',
        'port' => 3306,
        'charset' => 'utf8',
        'database_name' => '',
        'option' => [],
        'prefix' => '',
    ];

    /**
     * 事务记录,支持事务的嵌套调用,永远只会开启一次,提交一次
     * @var int
     */
    protected $_transactionCount = 0;
    protected $_table;
    protected $_join = [];
    protected $_field = '*';
    protected $_where = [];
    protected $_order;
    protected $_limit;
    protected $_group;
    protected $_cache = false;

    /**
     * 加载配置
     */
    protected function _initConfig() {}


    /**
     * 激活连接
     * @param array $conf
     */
    public function setActive($conf = []) {
        if (is_null($this->_medoo)) {
            if($conf){
                $this->_config = $conf;
            }
            if(extension_loaded('PDO')){
                $this->_medoo = new Medoo($this->_config);
            }else{
                wm_500('not support: PDO');
            }
        }
    }

    /**
     * 获得底层驱动
     * @return Medoo
     */
    public function getDriver() {
        return $this->_medoo;
    }

    /**
     * 获得PDO对象
     * @return \PDO
     */
    public function getPdo() {
        return $this->_medoo->pdo;
    }

    /**
     * 设置表名
     * @param $table
     * @return Connection
     */
    public function table($table) {
        $this->from($table);
        return $this;
    }

    /**
     * 设置表名
     * @param $table
     * @return Connection
     */
    public function from($table) {
        $this->_table = $table;
        return $this;
    }

    /**
     * @param $join
     * @return Connection
     */
    public function join($join) {
        $this->_join = array_merge_recursive($this->_join, $join);
        return $this;
    }

    /**
     * @param $field
     * @return Connection
     */
    public function field($field) {
        if (is_array($field)) {
            if (is_array($this->_field)) {
                $this->_field = array_merge($this->_field, $field);
                return $this;
            }
        }
        $this->_field = $field;

        return $this;
    }

    /**
     * @param $where
     * @return Connection
     */
    public function where($where) {
        $this->_where = Arr::merge($this->_where, $where);
        return $this;
    }

    /**
     * @param $order
     * @return Connection
     */
    public function order($order) {
        if (is_array($order)) {
            $this->_order = $order;
            return $this;
//            if (is_array($this->_order)) {
//                $this->_order = array_merge($this->_order, $order);
//                return $this;
//            }
        }
        if (is_string($order)){
            $order = explode(' ',$order);
            if(count($order) > 1){
                $this->_order[$order[0]] = $order[1];
            }
        }
        return $this;
    }

    /**
     * @param $offset
     * @param null|int $limit
     * @return Connection
     */
    public function limit($offset, $limit = null) {
        if (strpos($offset, ',')) {
            $rel = explode(',', $offset);
            $offset = $rel['0'];
            $limit = $rel['1'];
        }
        if (is_null($limit)) {
            $limit = $offset;
            $offset = 0;
        }
        $this->_limit = [$offset, $limit];
        return $this;
    }

    /**
     * @param $group
     * @return Connection
     */
    public function group($group) {
        if (is_array($group)) {
            if (is_array($this->_group)) {
                $this->_group = array_merge($this->_group, $group);
                return $this;
            }
        }
        $this->_group = $group;
        return $this;
    }


    /**
     * 获取多条数据
     * @return array|bool|mixed
     */
    public function select() {
        if ($this->_join) {
            $res = $this->_medoo->select($this->_table, $this->_join, $this->_field, $this->_getWhere());
        } else {
            $res = $this->_medoo->select($this->_table, $this->_field, $this->_getWhere());
        }
        $this->cleanup();
        return $res;
    }

    /**
     * 获取单条数据
     * @return array|bool|mixed
     */
    public function find() {
        $res = null;
        $this->limit(1);
        if ($this->_join) {
            $res = $this->_medoo->select($this->_table, $this->_join, $this->_field, $this->_getWhere());
        } else {
            $res = $this->_medoo->select($this->_table, $this->_field, $this->_getWhere());
        }
        $this->cleanup();
        return $res ? $res[0] : $res;
    }

    /**
     * 新增数据
     * @param $datas
     * @return array|mixed
     */
    public function insert($datas) {
        $res = $this->_medoo->insert($this->_table, $datas);
        if(is_object($res)){
            $res = $this->_medoo->id();
        }
        $this->cleanup();
        return $res;
    }

    /**
     * 更新数据
     * @param $data
     * @return int
     */
    public function update($data) {
        $res = $this->_medoo->update($this->_table, $data, $this->_getWhere());
        $this->cleanup();
        return $res;
    }

    /**
     * 删除
     * @param string $mulitTable 使用删除多表,表名逗号分隔
     * @return int
     */
    public function delete($mulitTable = null) {
        $res = $this->_medoo->delete($this->_table, $this->_getWhere(), $mulitTable);
        $this->cleanup();
        return $res;
    }

    public function replace($columns) {
        $res = $this->_medoo->replace($this->_table, $columns, $this->_getWhere());
        $this->cleanup();
        return $res;
    }


    public function get() {
        $res = $this->_medoo->get($this->_table, $this->_field, $this->_getWhere());
        $this->cleanup();
        return $res;
    }

    public function has() {
        if (!$this->_join) {
            $res = $this->_medoo->has($this->_table, $this->_getWhere());
        } else {
            $res = $this->_medoo->has($this->_table, $this->_join, $this->_getWhere());
        }
        $this->cleanup();
        return $res;
    }

    public function count() {
        if (!$this->_join) {
            $res = $this->_medoo->count($this->_table, $this->_getWhere());
        } else {
            $res = $this->_medoo->count($this->_table, $this->_join, $this->_field, $this->_getWhere());
        }
        $this->cleanup();
        return $res;
    }

    public function max() {
        if (!$this->_join) {
            $res = $this->_medoo->max($this->_table, $this->_field, $this->_getWhere());
        } else {
            $res = $this->_medoo->max($this->_table, $this->_join, $this->_field, $this->_getWhere());
        }
        $this->cleanup();
        return $res;
    }

    public function min() {
        if (!$this->_join) {
            $res = $this->_medoo->min($this->_table, $this->_field, $this->_getWhere());
        } else {
            $res = $this->_medoo->min($this->_table, $this->_join, $this->_field, $this->_getWhere());
        }
        $this->cleanup();
        return $res;
    }

    public function avg() {
        if (!$this->_join) {
            $res = $this->_medoo->avg($this->_table, $this->_field, $this->_getWhere());
        } else {
            $res = $this->_medoo->avg($this->_table, $this->_join, $this->_field, $this->_getWhere());
        }
        $this->cleanup();
        return $res;
    }

    public function sumGroup() {
        $data = [];
        $table = $this->_table;
        $where = $this->_getWhere();
        $join = $this->_join;
        $filed = $this->_field;
        if (is_array($this->_field)) {
            foreach ($filed as $f) {
                $this->_table = $table;
                $this->_field = [$f];
                $this->_join = $join;
                $this->_where = $where;
                $data[$f] = $this->sum();
            }
            return $data;
        }
    }

    public function sum() {
        if (!$this->_join) {
            $res = $this->_medoo->sum($this->_table, $this->_field, $this->_getWhere());
        } else {
            $res = $this->_medoo->sum($this->_table, $this->_join, $this->_field, $this->_getWhere());
        }
        $this->cleanup();
        return $res;
    }

    public function info() {
        return $this->_medoo->info();
    }

    public function error() {
        return $this->_medoo->error();
    }

    public function lastQuery() {
        return $this->_medoo->lastQuery();
    }

    public function quote($string) {
        return $this->_medoo->quote($string);
    }

    public function query($query) {
        return $this->_medoo->query($query);
    }

    public function exec($query) {
        return $this->_medoo->exec($query);
    }

    /**
     * 开启事务
     */
    public function beginTransaction() {
        if ($this->_transactionCount === 0) {
            if($this->_medoo->beginTransaction(false)){
                $this->_transactionCount++;
            }
        }
    }

    /**
     * 事务回滚
     */
    public function rollback() {
        if ($this->_transactionCount > 0) {
            $this->_medoo->rollBack();
            $this->_transactionCount = 0;
        }
    }

    /**
     * 执行事务提交
     */
    public function commit() {
        if ($this->_transactionCount === 1) {
            $this->_medoo->commit();
        }
        if ($this->_transactionCount > 0) {
            $this->_transactionCount--;
        }
    }

    /**
     * 属性参数初始化
     */
    public function cleanup() {
        $this->_table = null;
        $this->_join = [];
        $this->_field = '*';
        $this->_where = [];
        $this->_order = null;
        $this->_limit = null;
        $this->_group = null;
        $this->_cache = true;
    }

    public function getParams() {
        return [
            'table' => $this->_table,
            'join' => $this->_join,
            'field' => $this->_field,
            'where' => $this->_where,
            'order' => $this->_order,
            'limit' => $this->_limit,
            'group' => $this->_group,
            'cache' => $this->_cache,
        ];
    }

    public function setParams($params) {
        empty($params['table']) || $this->_table = $params['table'];
        empty($params['join'])  || $this->_join  = $params['join'];
        empty($params['field']) || $this->_field = $params['field'];
        empty($params['where']) || $this->_where = $params['where'];
        empty($params['order']) || $this->_order = $params['order'];
        empty($params['limit']) || $this->_limit = $params['limit'];
        empty($params['group']) || $this->_group = $params['group'];
        empty($params['cache']) || $this->_cache = $params['cache'];
    }

    protected function _getWhere() {
        $where = $this->_where;
        if ($this->_order) {
            $where['ORDER'] = $this->_order;
        }
        if ($this->_limit) {
            $where['LIMIT'] = $this->_limit;
        }
        if ($this->_group) {
            $where['GROUP'] = $this->_group;
        }
        return $where;
    }

    public function _getLog(){
        return $this->_medoo->log();
    }
}
