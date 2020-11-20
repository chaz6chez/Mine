<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/17          #
# -------------------------- #

namespace Mine\Db;

use Mine\Helper\Arr;
use Mine\Helper\Exception;

/**
 * 数据库连接方法
 *  1.以链式调用的方式，组合拼接为medoo执行方法
 *
 * Class Connection
 * @package Mine\Db
 */
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
    protected $_error = '';

    /**
     * 数据库配置
     * @var array
     */
    protected $_config = [
        'database_type' => 'mysql',
        'server'        => '',
        'username'      => '',
        'password'      => '',
        'database_file' => '',
        'port'          => 3306,
        'charset'       => 'utf8',
        'database_name' => '',
        'option'        => [],
        'prefix'        => '',
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
    protected $_single = false;

    /**
     * 加载配置
     */
    protected function _initConfig() {}


    /**
     * 激活连接
     * @param array $conf
     * @return $this
     */
    public function setActive($conf = []) {
        if (is_null($this->_medoo)) {
            if($conf){
                $this->_config = $conf;
            }
            if(extension_loaded('PDO')){
                try{
                    $this->_medoo = new Medoo($this->_config);
                }catch (\PDOException $e){
                    $this->_active = false;
                    $this->_error = "db server exception : {$e->getMessage()}";
                }catch(\Exception $e){
                    $this->_active = false;
                    $this->_error = "exception : {$e->getMessage()}";
                }
                $this->_active = true;
                $this->_error = '';
            }else{
                $this->_active = false;
                $this->_error = 'not support: PDO';
            }
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function checker(){
        if(!$this->_active or !$this->_medoo){
            return false;
        }
        return true;
    }

    /**
     * @return string
     */
    public function getDbError(){
        return $this->_error;
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
     * @return \PDO|bool
     */
    public function getPdo() {
        if(!$this->checker()) return false;
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
     * @param bool $single
     * @return $this
     */
    public function single(bool $single = true){
        $this->_single = $single;
        return $this;
    }

    /**
     * @param $field
     * @return Connection
     */
    public function field($field) {
        if(is_string($field)){
            $fields = explode(',', $field);
            if(count($fields) > 1){
                $field = $fields;
            }
        }

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
        if(!$this->checker()) return false;
        if ($this->_join) {
            $res = $this->_medoo->setSingle($this->_single)->select($this->_table, $this->_join, $this->_field, $this->_getWhere());
        } else {
            $res = $this->_medoo->setSingle($this->_single)->select($this->_table, $this->_field, $this->_getWhere());
        }
        $this->cleanup();
        return $res;
    }

    /**
     * 获取单条数据
     * @param bool $for_update
     * @return array|bool|mixed
     */
    public function find($for_update = false) {
        if(!$this->checker()) return false;
        $res = null;
        $this->limit(1);
        $medoo = $this->_medoo->setSingle($this->_single);
        $where = $for_update ? $this->_getWhere([
            'FOR UPDATE' => true
        ]) : $this->_getWhere();
        if ($this->_join) {
            $res = $medoo->select($this->_table, $this->_join, $this->_field, $this->_getWhere());
        } else {
            $res = $medoo->select($this->_table, $this->_field, $where);
        }
        $this->cleanup();
        return $res ? $res[0] : $res;
    }

    /**
     * 新增数据
     * @param $datas
     * @param bool $filter
     * @return array|mixed
     */
    public function insert($datas,$filter = false) {
        if(!$this->checker()) return false;
        if($filter){
            $data = [];
            foreach ($datas as $key => $v){
                preg_match('/(?<column>[\s\S]*(?=\[(?<operator>\+|\-|\*|\/|\>\=?|\<\=?|\!|\<\>|\>\<|\!?~)\]$)|[\s\S]*)/', $key, $match);
                if(isset($match['operator'])){
                    $data[$match['column']] = $v;
                }else{
                    $data[$key] = $v;
                }
            }
            $datas = $data;
        }
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
        if(!$this->checker()) return false;
        $res = $this->_medoo->update($this->_table, $data, $this->_getWhere());
        if($res instanceof \PDOStatement){
            $res = $res->rowCount();
        }else{
            $res = false;
        }
        $this->cleanup();
        return $res;
    }

    /**
     * 删除
     * @param string $mulitTable 使用删除多表,表名逗号分隔
     * @return int
     */
    public function delete($mulitTable = null) {
        if(!$this->checker()) return false;
        $res = $this->_medoo->delete($this->_table, $this->_getWhere(), $mulitTable);
        if($res instanceof \PDOStatement){
            $res = $res->rowCount();
        }else{
            $res = false;
        }
        $this->cleanup();
        return $res;
    }

    /**
     * @param $columns
     * @return bool|\PDOStatement
     */
    public function replace($columns) {
        if(!$this->checker()) return false;
        $res = $this->_medoo->setSingle($this->_single)->replace($this->_table, $columns, $this->_getWhere());
        $this->cleanup();
        return $res;
    }


    public function get($for_update = false) {
        if(!$this->checker()) return false;
        $res = $this->_medoo->setSingle($this->_single)->get(
            $this->_table,
            $this->_field,
            $for_update ? $this->_getWhere([
                'FOR UPDATE' => true
            ]) : $this->_getWhere()
        );
        $this->cleanup();
        return $res;
    }

    public function hasTable() {
        if(!$this->checker()) return false;
        $res = $this->_medoo->hasTable($this->_table);
        $this->cleanup();
        return $res ? $res[0] : $res;
    }

    public function has() {
        if(!$this->checker()) return false;
        if (!$this->_join) {
            $res = $this->_medoo->has($this->_table, $this->_getWhere());
        } else {
            $res = $this->_medoo->has($this->_table, $this->_join, $this->_getWhere());
        }
        $this->cleanup();
        return $res;
    }

    public function count() {
        if(!$this->checker()) return false;
        if (!$this->_join) {
            $res = $this->_medoo->count($this->_table, $this->_getWhere());
        } else {
            $res = $this->_medoo->count($this->_table, $this->_join, $this->_field, $this->_getWhere());
        }
        $this->cleanup();
        return $res;
    }

    public function max() {
        if(!$this->checker()) return false;
        if (!$this->_join) {
            $res = $this->_medoo->max($this->_table, $this->_field, $this->_getWhere());
        } else {
            $res = $this->_medoo->max($this->_table, $this->_join, $this->_field, $this->_getWhere());
        }
        $this->cleanup();
        return $res;
    }

    public function min() {
        if(!$this->checker()) return false;
        if (!$this->_join) {
            $res = $this->_medoo->min($this->_table, $this->_field, $this->_getWhere());
        } else {
            $res = $this->_medoo->min($this->_table, $this->_join, $this->_field, $this->_getWhere());
        }
        $this->cleanup();
        return $res;
    }

    public function avg() {
        if(!$this->checker()) return false;
        if (!$this->_join) {
            $res = $this->_medoo->avg($this->_table, $this->_field, $this->_getWhere());
        } else {
            $res = $this->_medoo->avg($this->_table, $this->_join, $this->_field, $this->_getWhere());
        }
        $this->cleanup();
        return $res;
    }

    public function sumGroup() {
        if(!$this->checker()) return false;
        $data = [];
        $table = $this->_table;
        $where = $this->_getWhere();
        $join = $this->_join;
        if (is_array($this->_field)) {
            foreach ($this->_field as $f) {
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
        if(!$this->checker()) return false;
        if (!$this->_join) {
            $res = $this->_medoo->sum($this->_table, $this->_field, $this->_getWhere());
        } else {
            $res = $this->_medoo->sum($this->_table, $this->_join, $this->_field, $this->_getWhere());
        }
        $this->cleanup();
        return $res;
    }

    public function info() {
        if(!$this->checker()) return false;
        return $this->_medoo->info();
    }

    public function error() {
        if(!$this->checker()) return false;
        return $this->_medoo->error();
    }

    public function lastQuery() {
        if(!$this->checker()) return false;
        return $this->_medoo->lastQuery();
    }

    public function last(){
        if(!$this->checker()) return false;
        return $this->_medoo->last();
    }

    public function quote($string) {
        if(!$this->checker()) return false;
        return $this->_medoo->quote($string);
    }

    public function query($query) {
        if(!$this->checker()) return false;
        return $this->_medoo->query($query);
    }

    public function exec($query) {
        if(!$this->checker()) return false;
        return $this->_medoo->exec($query);
    }

    public function isPDOStatement($data){
        return boolval($data instanceof \PDOStatement);
    }

    /**
     * 开启事务
     * @param bool $throw
     * @return array
     */
    public function beginTransaction($throw = true) {
        if(!$this->checker()) return [false,$this->_error];
        try{
            if($res = $this->_medoo->beginTransaction($throw)){
                $this->_transactionCount++;
                return [
                    true,
                    $res
                ];
            }
            return [
                false,
                $res
            ];
        }catch (Exception $e){
            return [
                false,
                "{$e->getCode()}|{$e->getMessage()}"
            ];
        }
    }

    /**
     * @return bool
     */
    public function inTransaction(){
        return $this->_medoo->inTransaction();
    }

    /**
     * 事务回滚
     * @param bool $throw
     * @return array
     */
    public function rollback($throw = true) {
        if(!$this->checker()) return [false,$this->_error];
        try{
            if($res = $this->_medoo->rollback($throw)){
                $this->_transactionCount = 0;
                return [
                    true,
                    $res
                ];
            }
            return [
                false,
                $res
            ];
        }catch (Exception $e){
            return [
                false,
                "{$e->getCode()}|{$e->getMessage()}"
            ];
        }
    }

    /**
     * 执行事务提交
     * @param bool $throw
     * @return array
     */
    public function commit($throw = true) {
        if(!$this->checker()) return [false,$this->_error];
        try{
            if($res = $this->_medoo->commit($throw)){
                $this->_transactionCount = 0;
                return [
                    true,
                    $res
                ];
            }
            return [
                false,
                $res
            ];
        }catch (Exception $e){
            return [
                false,
                "{$e->getCode()}|{$e->getMessage()}"
            ];
        }
    }

    /**
     * 属性参数初始化
     */
    public function cleanup() {
//        $this->_table = null;
        $this->_join       = [];
        $this->_field      = '*';
        $this->_where      = [];
        $this->_order      = null;
        $this->_limit      = null;
        $this->_group      = null;
        $this->_cache      = true;
        $this->_single     = false;
        $this->_error      = $this->_medoo->error();
    }

    public function getParams() {
        return [
            'table'      => $this->_table,
            'join'       => $this->_join,
            'field'      => $this->_field,
            'where'      => $this->_where,
            'order'      => $this->_order,
            'limit'      => $this->_limit,
            'group'      => $this->_group,
            'cache'      => $this->_cache,
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

    protected function _getWhere(array $array = []) {
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
        return array_merge($where,$array);
    }

    public function getLog(){
        if(!$this->checker()) return false;
        return $this->_medoo->log();
    }

    public function getConfig($param = null){
        if(!$param){
            return $this->_config;
        }
        return isset($this->_config[$param]) ? $this->_config[$param] : '';
    }
}
