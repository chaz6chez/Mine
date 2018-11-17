<?php
/**
 * Who ?: Chaz6chez
 * How !: 250220719@qq.com
 * Where: http://chaz6chez.top
 * Time : 2018/11/13|23:54
 * What : Creating Fucking Bug For Every Code
 */
namespace core\helper;

/**
 * 进程间共享数据组件
 * todo 更多的方法
 * Class Apcu
 * @package core\helper
 */
final class Apcu{
    /**
     * @var Apcu
     */
    private static $_instance = null;

    /**
     * Apcu constructor.
     */
    public function __construct() {
        if(!extension_loaded('apcu')){
            wm_500('no support: apcu');
        }
        if(!ini_get('apc.enable_cli')){
            wm_500('apc.enable_cli was disable');
        }
    }

    /**
     * 实例
     * @return Apcu
     */
    final public static function instance() {
        if (!isset(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 判断是否存在
     * @param $keys
     * @return array|bool|string[]
     */
    public function has($keys) {
        if (!is_array($keys)) {
            return apcu_exists($keys);
        }
        $existing = array();
        foreach ($keys as $k) {
            if (apcu_exists($k)) {
                $existing[$k] = true;
            }
        }
        return $existing;
    }

    /**
     * 获取缓存
     * @param $key
     * @param bool $success
     * @return array|mixed
     */
    public function get($key, &$success = false) {
        if (!\is_array($key)) {
            return unserialize(apcu_fetch($key, $success));
        }
        $succeeded = true;
        $values = [];
        foreach ($key as $k) {
            $v = apcu_fetch($k, $success);
            if ($success) {
                $values[$k] = unserialize($v);
            } else {
                $succeeded = false;
            }
        }
        $success = $succeeded;
        return $values;
    }

    /**
     * 设置缓存(覆盖)
     * @param $key
     * @param $value
     * @param null $expire
     * @return array|bool
     */
    public function set($key, $value, $expire = null) {
        $value = is_scalar($value) ? $value : serialize($value);
        if (!\is_array($key)) {
            return apcu_store($key, $value, $expire);
        }
        $errors = array();
        foreach ($key as $k => $v) {
            if (!apcu_store($k, $v, $expire)) {
                $errors[$k] = -1;
            }
        }
        return $errors;
    }

    /**
     * 新增缓存(始终新增)
     * @param $key
     * @param $value
     * @param null $expire
     * @return array|bool
     */
    public function add($key, $value, $expire = null) {
        $value = is_scalar($value) ? $value : 'wm_serialize:' . serialize($value);
        if (!is_array($key)) {
            return apcu_add($key, $value, $expire);
        }
        $errors = [];
        foreach ($key as $k => $v) {
            if (!apcu_add($key, $value, $expire)) {
                $errors[$k] = -1;
            }
        }
        return $errors;
    }

    /**
     * 删除键值
     * @param $key
     * @return bool|string[]
     */
    public function rm($key) {
        if (!is_array($key)) {
            return apcu_delete($key);
        }
        $success = true;
        foreach ($key as $k) {
            $success = apcu_delete($k) and $success;
        }
        return $success;
    }

    /**
     * 清除缓存
     * @return bool
     */
    public function clear() {
        return apcu_clear_cache();
    }
}
