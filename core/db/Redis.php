<?php

namespace core\db;

use core\helper\Tools;
use core\lib\Config;

/**
 * Redis是单例模式
 *  1.$_instance并不是一个容器
 *  2.不需要关注其内存问题
 *
 *  2018-11-22
 *      1.解决10054问题
 *
 * Class Redis
 * @package core\db
 */
class Redis extends Driver {

    protected $options = [];
    /**
     * @var Redis
     */
    private static $_instance = null;

    /**
     * Redis constructor.
     * @param array $options 缓存参数
     */
    public function __construct($options = []) {
        $this->_ext(true);
        $this->options = Config::get('redis');
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        if (!$this->handler or !$this->handler instanceof \Redis) {
            $this->handler = new \Redis();
        }
        if ($this->options['persistent']) {
            $this->handler->pconnect($this->options['host'], $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']);
        } else {
            $this->handler->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
        }
        if ('' != $this->options['password']) {
            $this->handler->auth($this->options['password']);
        }
        if (0 != $this->options['select']) {
            $this->handler->select($this->options['select']);
        }
    }

    /**
     * @param bool $throw
     * @return bool
     */
    private function _ext($throw = false){
        if (!extension_loaded('redis')) {
            if($throw){
                wm_500('not support: redis');
            }
            return false;
        }
    }

    /**
     * 实例
     * @return Redis|bool|null
     */
    final public static function instance() {
        if (!isset(self::$_instance) or !self::$_instance instanceof Redis) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 判断缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public function has($name) {
        $this->_ext();
        $this->call();
        return $this->handler->exists($this->getCacheKey($name));
    }

    /**
     * @param $name
     * @return array|bool
     */
    public function keys($name){
        $this->_ext();
        $this->call();
        return $this->handler->keys($this->getCacheKey($name));
    }

    /**
     * @param $name
     * @param bool $default
     * @return array
     */
    public function getKeysValue($name,$default = false){
        $keys = $this->keys($name);
        $result = [];
        if($keys){
            if(count($keys) < 5000){
                foreach ($keys as $key){
                    $result[] = $this->get($key,$default);
                }
            }
        }
        return $result;
    }

    /**
     * @param $name
     * @return array
     */
    public function hGetAll($name){
        $this->_ext();
        $this->call();
        $h = $this->handler->hGetAll($this->getCacheKey($name));
        if($h){
            foreach ($h as &$value){
                $value = ($json = is_json($value,true)) ? $json : $value;
            }
        }
        return $h;
    }

    /**
     * @param $key
     * @return array
     */
    public function hGetAllNew($key) {
        $this->_ext();
        $this->call();
        $key = $this->getCacheKey($key);
        $keys = $this->handler->hKeys($key);
        $data = [];
        if(!$keys) return $data;
        $data = $this->handler->hMGet($key, $keys);
        return $data ? $data : [];
    }

//    public function hGetAll_test($key){
//        $it = NULL;
//        $this->handler->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);
//        $data = [];
//        while($arr = $this->handler->hScan($this->getCacheKey($key),null)) {
//            foreach($arr as $k => $v) {
//                $data[$k] = $v;
//            }
//        }
//        return $data;
//    }

    /**
     * @param $name
     * @param $key
     * @return string
     */
    public function hGet($name,$key){
        $this->_ext();
        $this->call();
        $value = $this->handler->hGet($this->getCacheKey($name),$key);
        $value = ($json = is_json($value,true)) ? $json : $value;
        return $value;
    }

    /**
     * @param $name
     * @param array $array
     * @return bool
     */
    public function hSetArray($name,array $array){
        $this->_ext();
        $this->call();
        foreach ($array as $key => $value){
            $v = is_scalar($value) ? $value : json_encode($value,JSON_UNESCAPED_UNICODE);
            $this->handler->hSet($this->getCacheKey($name),$key,$v);
        }
        return true;
    }

    /**
     * @param $name
     * @param $key
     * @param $value
     * @return bool|int
     */
    public function hSet($name,$key,$value){
        $this->_ext();
        $this->call();
        $v = is_scalar($value) ? $value : json_encode($value,JSON_UNESCAPED_UNICODE);
        return $this->handler->hSet($this->getCacheKey($name),$key,$v);
    }

    /**
     * @param $name
     * @param array|string $keys
     * @return bool|int
     */
    public function hDel($name,$keys){
        $this->_ext();
        $this->call();
        if(is_array($keys)){
            foreach ($keys as $key){
                $this->handler->hDel($this->getCacheKey($name),$key);
            }
            return true;
        }
        return $this->handler->hDel($this->getCacheKey($name),$keys);
    }


    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get($name, $default = false) {
        $this->_ext();
        $this->call();
        $value = $this->handler->get($this->getCacheKey($name));
        if (is_null($value) || false === $value) {
            return $default;
        }
        try {
            $result = ($json = json_decode($value,true)) ? $json : $value;
        } catch (\Exception $e) {
            $result = $default;
        }
        return $result;
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value 存储数据
     * @param integer|\DateTime $expire 有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null) {
        $this->_ext();
        $this->call();
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        if ($expire instanceof \DateTime) {
            $expire = $expire->getTimestamp() - time();
        }
        if ($this->tag && !$this->has($name)) {
            $first = true;
        }
        $key = $this->getCacheKey($name);
        $value = is_scalar($value) ? $value : json_encode($value,JSON_UNESCAPED_UNICODE);
        if ($expire) {
            $result = $this->handler->setex($key, $expire, $value);
        } else {
            $result = $this->handler->set($key, $value);
        }
        isset($first) && $this->setTagItem($key);
        return $result;
    }

    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param  string $name 缓存变量名
     * @param  int $step 步长
     * @return false|int
     */
    public function inc($name, $step = 1) {
        $this->_ext();
        $this->call();
        $key = $this->getCacheKey($name);
        return $this->handler->incrby($key, $step);
    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param  string $name 缓存变量名
     * @param  int $step 步长
     * @return false|int
     */
    public function dec($name, $step = 1) {
        $this->_ext();
        $this->call();
        $key = $this->getCacheKey($name);
        return $this->handler->decrby($key, $step);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function rm($name) {
        $this->_ext();
        $this->call();
        return $this->handler->delete($this->getCacheKey($name));
    }

    /**
     * 清除缓存
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public function clear($tag = null) {
        $this->_ext();
        $this->call();
        if ($tag) {
            // 指定标签清除
            $keys = $this->getTagItem($tag);
            foreach ($keys as $key) {
                $this->handler->delete($key);
            }
            $this->rm('tag_' . md5($tag));
            return true;
        }
        return $this->handler->flushDB();
    }

    /**
     * 检查连接
     * @return bool|string
     */
    public function call(){
        try{
            $this->handler->ping();
        }catch (\RedisException $e){
            if(Tools::isRedisTimeout($e)){
                if($this->options['persistent']){
                    $this->handler->close();
                }
                $this->handler = null;
                self::$_instance = null;
                self::$_instance = new self();
                return true;
            }
            return false;
        }
        return true;
    }
}
