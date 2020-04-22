<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/20           #
# -------------------------- #
namespace Mine\Core;

use Mine\Helper\Arr;
use Mine\Helper\Exception;
use Mine\Helper\Tools;

/**
 * 配置类
 *
 *  1.配置项的内容保存在静态变量中,进程不退出则变量无法释放
 *  2.任意一次变更则会在当前主进程的所有子进程中生效
 *  3.init()的更新为完整覆盖，load()的更新为更新覆盖
 * Class Config
 * @package core\lib
 */
class Config {

    /**
     * @var array 配置
     */
    protected static $_config;

    protected static $_path = '';

    protected static $_tag = [];

    /**
     * 初始化
     * 加载系统配置,环境配置
     */
    public static function init() {
        if(file_exists($path = self::getPath())){
            self::$_config = require $path;
        }
    }

    /**
     * @param string $path
     */
    public static function setPath(string $path){
        self::$_path = $path;
    }

    /**
     * @return string
     */
    public static function getPath() : string {
        return self::$_path;
    }

    /**
     * @param string $path
     * @param bool $tag
     */
    public static function setTag(string $path, bool $tag){
        self::$_tag[$path] = $tag;
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function getTag(string $path) : bool {
        if(!isset(self::$_tag[$path])){
            return false;
        }
        return self::$_tag[$path];
    }

    /**
     * @return array
     */
    public static function getTags() : array {
        return self::$_tag;
    }

    /**
     * 加载一个配置文件合并到配置缓存中
     * @param $path
     */
    public static function load($path) {
        if(is_array($path)){
            $config = $path;
        }else if(file_exists($path)){
            $config = [];
            if(!self::getTag($path)){
                self::setPath($path);
                $config = require self::getPath();
                self::setTag($path, true);
            }
        }else{
            $config = [];
        }
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
            Tools::SafeEcho($e->getMessage(),'CONFIG ERROR');
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