<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/20           #
# -------------------------- #
namespace core\lib;

/**
 * 环境变量类
 *
 *  1.默认加载server目录的.env文件
 *  2.init仅执行一次，多次加载请使用load
 *  3.系统环境变量get时使用env.前缀
 *
 * Class Env
 * @package core\lib
 */
class Env {
    /**
     * 环境变量数据
     * @var array
     */
    protected static $_env = [];
    /**
     * @var bool 状态
     */
    protected static $_init = false;

    /**
     * 系统环境变量预加载
     */
    private static function _initEnv(){
        $_ENV = getenv();
        $env = [];
        foreach ($_ENV as $key => $value){
            $env["ENV_{$key}"] = $value;
        }
        self::$_env = array_merge(self::$_env,$env);
    }

    /**
     * 预加载
     */
    public static function init() {
        if(!self::$_init){
            self::$_init = true;
            self::load();
            self::_initEnv();
        }
    }

    /**
     * 清除
     */
    public static function clean(){
        self::$_init = false;
        self::$_env = [];
    }

    /**
     * 读取环境变量定义文件
     * @access public
     * @param  string    $file  环境变量定义文件
     * @return void
     */
    public static function load($file = ''){
        if(!$file) {
            $file = SERVER_PATH . '/.env';
        }
        $env = parse_ini_file($file, true);
        self::set($env);
    }

    /**
     * 获取环境变量值
     * @param null $name
     * @param null $default
     * @return array|bool|false|mixed|null|string
     */
    public static function get($name = null, $default = null){
        if (is_null($name)) {
            return self::$_env;
        }
        $name = strtoupper(str_replace('.', '_', $name));
        if (isset(self::$_env[$name])) {
            return self::$_env[$name];
        }
        return $default;
    }

    /**
     * 设置环境变量值
     * @access public
     * @param  string|array  $env   环境变量
     * @param  mixed         $value  值
     * @return void
     */
    public static function set($env, $value = null){
        if($env){
            if (is_array($env)) {
                $env = array_change_key_case($env, CASE_UPPER);

                foreach ($env as $key => $val) {
                    if (is_array($val)) {
                        foreach ($val as $k => $v) {
                            self::$_env[$key . '_' . strtoupper($k)] = $v;
                        }
                    } else {
                        self::$_env[$key] = $val;
                    }
                }
            } else {
                $name = strtoupper(str_replace('.', '_', $env));
                self::$_env[$name] = $value;
            }
        }
    }
}
