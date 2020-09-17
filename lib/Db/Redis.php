<?php

namespace Mine\Db;


use Mine\Core\Config;
use Mine\Definition\Define;
use Mine\Helper\Tools;

/**
 * Redis是单例模式
 *  1.$_instance并不是一个容器
 *  2.不需要关注其内存问题
 *
 *  2018-11-22
 *      1.解决10054问题
 *
 *  2019-01-01
 *      1.新增hGetAllNew替代hGetAll
 *
 * Class Redis
 * @package core\db
 */
class Redis extends Driver {

    protected $options   = [];
    protected $is_active = null;
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
        $this->options = Config::get(Define::CONFIG_REDIS);
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        if (!$this->handler or !$this->handler instanceof \Redis) {
            $this->handler = new \Redis();
        }
        try{
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
        }catch (\Exception $e){
            $this->is_active = false;
            //todo 日志
        }
    }

    /**
     * @param bool $throw
     * @return bool
     */
    private function _ext($throw = false){
        if (!extension_loaded('redis')) {
            if($throw){
                Tools::Http500('not support: redis');
            }
            return false;
        }
        return true;
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
     * 检查后删除
     * @param $name
     * @return bool
     */
    public function rmAfterCheck($name){
        if($this->has($name)){
            return $this->rm($name);
        }
        return true;
    }

    /**
     * 判断缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public function has($name) {
        if(!$this->call()) return false;
        return $this->handler->exists($this->getCacheKey($name));
    }

    /**
     * @param $name
     * @return array|bool
     */
    public function keys($name){
        if(!$this->call()) return false;
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
     * @return array|bool
     */
    public function hGetAll($name){
        if(!$this->call()) return false;
        $h = $this->handler->hGetAll($this->getCacheKey($name));
        if($h){
            foreach ($h as &$value){
                $value = ($json = Tools::isJson($value,true)) ? $json : $value;
            }
        }
        return $h;
    }

    /**
     * @param $key
     * @return array|bool
     */
    public function hGetAllNew($key) {
        if(!$this->call()) return false;
        $key = $this->getCacheKey($key);
        $keys = $this->handler->hKeys($key);
        $data = [];
        if(!$keys) return $data;
        $json = $this->handler->hMGet($key, $keys);
        $data = ($json = Tools::isJson($json,true)) ? $json : [];
        return $data;
    }


    /**
     * @param $name
     * @param $key
     * @return string|bool
     */
    public function hGet($name,$key){
        if(!$this->call()) return false;
        $value = $this->handler->hGet($this->getCacheKey($name),$key);
        $value = ($json = Tools::isJson($value,true)) ? $json : $value;
        return $value;
    }

    /**
     * @param $name
     * @param array $array
     * @return bool
     */
    public function hSetArray($name,array $array){
        if(!$this->call()) return false;
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
        if(!$this->call()) return false;
        $v = is_scalar($value) ? $value : json_encode($value,JSON_UNESCAPED_UNICODE);
        return $this->handler->hSet($this->getCacheKey($name),$key,$v);
    }

    /**
     * @param $name
     * @param array|string $keys
     * @return bool|int
     */
    public function hDel($name,$keys){
        if(!$this->call()) return false;
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
        if(!$this->call()) return false;
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
     * @param $index
     * @return $this
     */
    public function select($index){
        if($this->call()) $this->handler = $this->handler->select($index);
        return $this;
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
        if(!$this->call()) return false;
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
        if(!$this->call()) return false;
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
        if(!$this->call()) return false;
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
        if(!$this->call()) return false;
        return $this->handler->del($this->getCacheKey($name));
    }

    /**
     * 清除缓存
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public function clear($tag = null) {
        if(!$this->call()) return false;
        if ($tag) {
            // 指定标签清除
            $keys = $this->getTagItem($tag);
            foreach ($keys as $key) {
                $this->handler->del($key);
            }
            $this->rm('tag_' . md5($tag));
            return true;
        }
        return $this->handler->flushDB();
    }

    /**
     * 检查连接
     * @param bool $throw
     * @return bool
     */
    public function call($throw = false){
        try{
            $this->handler->ping('');
        }catch (\RedisException $e){
            $this->_log($e);
            if(Tools::isRedisTimeout($e)){
                if($this->options['persistent'] and $this->handler instanceof \Redis){
                    $this->handler->close();
                }
                $this->handler = null;
                $this->is_active = null;
                self::$_instance = null;
                self::$_instance = new self();
                return true;
            }
            if($throw){
                Tools::Http500('redis server timeout');
            }
            return false;
        }catch (\Exception $e){
            $this->_log($e);
            if($throw){
                Tools::Http500('redis server timeout');
            }
            return false;
        }
        return true;
    }

    protected function _log(\Exception $e){
        Tools::log(Define::CONFIG_REDIS,[
            $e->getCode(),
            $e->getMessage()
        ]);
    }
}
