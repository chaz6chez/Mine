<?php

namespace core\db;

use core\lib\Config;
use core\helper\Exception;

/**
 * Redis是单例模式
 *  1.$_instance并不是一个容器
 *  2.不需要关注其内存问题
 *
 * Class Redis
 * @package core\db
 */
class Redis extends Driver {

    protected $options = [];
    /**
     * @var \Redis
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
        if (!self::$_instance instanceof Redis) {
            self::$_instance = new \Redis();
        }
        $this->handler = self::$_instance;
        if ($this->options['persistent']) {
            self::$_instance = $this->handler->pconnect($this->options['host'], $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']);
        } else {
            self::$_instance = $this->handler->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
        }
        if ('' != $this->options['password']) {
            self::$_instance = $this->handler->auth($this->options['password']);
        }
        if (0 != $this->options['select']) {
            self::$_instance = $this->handler->select($this->options['select']);
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
        if (!isset(self::$_instance)) {
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
        return $this->handler->exists($this->getCacheKey($name));
    }

    /**
     * @param $name
     * @return array|bool
     */
    public function keys($name){
        $this->_ext();
        return $this->handler->keys($this->getCacheKey($name));
    }

    /**
     * @param $name
     * @return bool
     */
    public function getKeys($name){
        $this->_ext();
        return $this->handler->getKeys($this->getCacheKey($name));
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
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get($name, $default = false) {
        $this->_ext();
        $value = $this->handler->get($this->getCacheKey($name));
        if (is_null($value) || false === $value) {
            return $default;
        }
        try {
            $result = 0 === strpos($value, 'wm_serialize:') ? unserialize(substr($value, 13)) : $value;
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
        $value = is_scalar($value) ? $value : 'wm_serialize:' . serialize($value);
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
}
