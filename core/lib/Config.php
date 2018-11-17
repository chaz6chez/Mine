<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/20           #
# -------------------------- #
namespace core\lib;

use core\helper\Arr;
use core\helper\Exception;

class Config {

    /**
     * @var array 配置
     */
    protected static $_config;

    /**
     * 初始化
     * 加载系统配置,环境配置
     */
    public static function init() {
        self::$_config = require COMMON_PATH . '/configs.php';
    }

    /**
     * 加载一个配置文件合并到配置缓存中
     * @param $path
     */
    public static function load($path) {
        $config = is_array($path) ? $path : require $path;
        self::$_config = Arr::merge(self::$_config, $config);
    }

    /**
     * 获取一个配置的值,使用.分割的路径访问
     * @param null $path
     * @param null $default
     * @return array|null
     */
    public static function get($path = null, $default = null) {
        try{
            return is_null($path) ?
                self::$_config :
                Arr::path(self::$_config, $path, $default, '.');
        }catch (Exception $e){
            if(defined('DEBUG') and DEBUG){
                dump($e->getMessage());
            }
            return [];
        }
    }

    /**
     * 动态设置配置,使用.分割的路径访问
     * @param $path
     * @param $value
     * @return mixed
     */
    public static function set($path, $value) {
        Arr::setPath(self::$_config, $path, $value);
        return $value;
    }
}