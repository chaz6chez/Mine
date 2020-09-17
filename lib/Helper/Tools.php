<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/17            #
# -------------------------- #
namespace Mine\Helper;

use Mine\Core\Config;
use Mine\Core\Env;

class Tools{

    public static function log($module,$log){
        //todo
    }

    # ******************* Launcher tools ******************* #

    /**
     * 获取launcher配置
     * @param $path
     * @param array $argv
     * @param bool $base
     * @return string
     */
    public static function Launcher($path,array $argv,$base = true){
        global $LAUNCHER_PATH;
        if($base){
            self::LauncherBase($path);
        }
        if(isset($argv[3])){
            if($argv[3] == 'all'){
                goto res;
            }
            if(!file_exists($path = "{$path}/{$argv[3]}")){
                exit("{$argv[3]} launcher not defined.\n");
            }
            $LAUNCHER_PATH = $path;
            return "{$path}/launcher_*.php";
        }
        res:
        return $path.'/*/launcher_*.php';
    }

    /**
     * @param $file
     * @param array $argv
     * @return bool
     */
    public static function LauncherSkip($file,array $argv){
        global $LAUNCHER_PATH;
        if(isset($argv[4])){
            if($file == $argv[4]){
                return true;
            }
        }
        return false;
    }

    /**
     * @param $path
     */
    public static function LauncherDefines($path){
        if(file_exists($file = $path.'/launcher_defines.php')){
            require_once $file;
            return;
        }
        exit('define file of the launcher was not found'.PHP_EOL);
    }

    /**
     * @param $path
     */
    public static function LauncherFunctions($path){
        if(file_exists($file = $path.'/functions.php')){
            require_once $file;
            return;
        }
        exit('functions file of the launcher was not found'.PHP_EOL);
    }

    /**
     * @param $path
     */
    public static function LauncherBase($path){
        foreach(glob("{$path}/launcher_*.php") as $launcher) {
            if(!file_exists($launcher)){
                exit("{$launcher} file was not found".PHP_EOL);
            }
            require_once $launcher;
        }
        return;
    }

    /**
     * @param string $configPath
     * @param string $envPath
     * @param string $templatePath
     */
    public static function LauncherSupport(string $configPath, string $envPath, string $templatePath = ''){
        self::LauncherConfig($configPath);
        self::LauncherEnv($envPath);
        if($templatePath){
            self::LauncherTemplate($templatePath);
        }
    }

    public static function LauncherConfig(string $configPath){
        Config::setPath($configPath);
    }

    public static function LauncherEnv(string $envPath){
        Env::setDefaultFile($envPath);
    }

    public static function LauncherTemplate(string $templatePath){
        Template::setTplDir($templatePath);
    }


    # ******************* Checker tools ******************* #

    /**
     * 判断是否是唯一键重复的错误
     * @param \PDOException $e
     * @return bool
     */
    public static function isDuplicateError(\PDOException $e) : bool {
        return $e->getCode() == 23000;
    }

    /**
     * @param $string
     * @param bool $get
     * @return bool|mixed
     */
    public static function isJson($string,bool $get = false){
        if(@json_decode($string)){
            if(json_last_error() != JSON_ERROR_NONE){
                return false;
            }
            if($get){
                return json_decode($string,true);
            }
            return true;
        }
        return false;
    }

    /**
     * 判断MYSQL是否是被踢出
     * @param \PDOException $e
     * @return bool
     */
    public static function isGoneAwayError(\PDOException $e) : bool {
        return ($e->errorInfo[1] == 2006 or $e->errorInfo[1] == 2013);
    }

    /**
     * 判断REDIS是否超时
     * @param \RedisException $e
     * @return bool
     */
    public static function isRedisTimeout(\RedisException $e) : bool {
        return ($e->getCode() == 10054 or $e->getMessage() == 10054);
    }

    /**
     * 是否是全局启动
     * @return bool
     */
    public static function isGlobalStart() : bool {
        if (
            defined('GLOBAL_START') and
            GLOBAL_START
        ){
            return true;
        }
        return false;
    }

    /**
     * WinOs
     * @param bool $exit
     * @return bool
     */
    public static function isWinOs($exit = false) : bool {
        if(strpos(strtolower(PHP_OS), 'win') === 0) {
            if($exit){
                exit('please use launcher.bat'.PHP_EOL);
            }
            return true;
        }
        return false;
    }

    /**
     * 是否在debug模式
     * @return bool
     */
    public static function isDebug() : bool {
        if(defined('DEBUG') and DEBUG){
            return true;
        }
        return false;
    }

    /**
     * 判断grpc拓展是否支持
     * @param bool $master
     * @return array
     */
    public static function grpcExtensionSupport($master = true){
        if(!extension_loaded('grpc')){
            if($master){
                echo "no support grpc\n";
                exit;
            }
            return [false,"no support grpc\n"];
        }
        return [true,null];
    }

    /**
     * 判断grpc拓展是否支持
     * @param bool $master
     * @return array
     */
    public static function grpcForkSupport($master = true){
        if(PHP_OS === 'Linux'){
            if(
                getenv('GRPC_ENABLE_FORK_SUPPORT') != '1' or
                getenv('GRPC_POLL_STRATEGY') != 'epoll1'
            ){
                if($master){
                    echo "grpc extension environment variables not ready\n";
                    exit;
                }
                return [false,"grpc extension environment variables not ready\n"];
            }
        }
        return [true,null];
    }

    /**
     * 主进程启动判断器
     * @param bool $throw
     * @return bool
     */
    public static function processChecker($throw = true){
        $cmd = 'ps axu|grep "WorkerMan: master process"|grep -v "grep"|wc -l';
        $ret = shell_exec($cmd);
        if($throw){
            echo "master process is ready\n";
            exit;
        }
        return (rtrim($ret, "\r\n") === '0') ? false : true;
    }

    # ******************* Launcher tools ******************* #

    /**
     * @return int|mixed
     */
    public static function getNowTime(){
        return isset($GLOBALS['NOW_TIME']) ? $GLOBALS['NOW_TIME'] : time();
    }
    /**
     * @return float
     */
    public static function getMemoryUsed(){
        return round(memory_get_usage(false) / 1024 / 1024, 2);
    }

    /**
     * @param string $prefix
     * @return string
     */
    public static function randomString($prefix = '') : string {
        return md5(self::UUIDFake($prefix));
    }

    /**
     * @param string $prefix
     * @return string
     */
    public static function UUID($prefix = '') : string {
        if(extension_loaded('uuid') and function_exists('uuid_create')){
            return $prefix . uuid_create(1);
        }
        return self::UUIDFake($prefix);
    }

    /**
     * @param string $uuid_a
     * @param string $uuid_b
     * @return bool
     * @throws Exception
     */
    public static function UUIDCompare(string $uuid_a,string $uuid_b) : bool {
        if(
            extension_loaded('uuid') and
            function_exists('uuid_compare')
        ){
            return uuid_compare($uuid_a,$uuid_b);
        }
        throw new Exception('not support: uuid');
    }

    /**
     * @param $prefix
     * @return string
     */
    public static function UUIDFake($prefix = '') : string {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid  = substr($chars,0,8) . '-';
        $uuid .= substr($chars,8,4) . '-';
        $uuid .= substr($chars,12,4) . '-';
        $uuid .= substr($chars,16,4) . '-';
        $uuid .= substr($chars,20,12);
        return $prefix . $uuid;

    }

    # ******************* HTTP tools ******************* #

    /**
     * @param $content
     * @param bool $replace
     * @param int $http_response_header
     */
    public static function Header($content,$replace = true,$http_response_header = 0){
        \Workerman\Protocols\Http::header($content,$replace,$http_response_header);
    }

    /**
     * @param string $msg
     * @param bool $echo
     */
    public static function Http500($msg = '500 Internal Server Error', $echo = false){
        @ob_clean();
        self::Header("HTTP/1.1 500 Internal Server Error");
        if($echo){
            self::SafeEcho($msg);
        }
        self::End(
            '<html><head><title>500 Internal Server Error</title></head><body><center><h3>500 Internal Server Error [Server]</h3></center></body></html>'
            ,true);
    }

    /**
     * @param string $msg
     * @param bool $echo
     */
    public static function Http404($msg = '404 Not Found', $echo = true){
        @ob_clean();
        self::Header("HTTP/1.1 404 Not Found");
        if($echo){
            self::SafeEcho($msg);
        }
        self::End(
            '<html><head><title>404 File not found</title></head><body><center><h3>404 Not Found [Server]</h3></center></body></html>'
            ,true);
    }

    /**
     * @param string $msg
     * @param bool $echo
     */
    public static function Http403($msg = '403 Forbidden', $echo = false){
        @ob_clean();
        self::Header("HTTP/1.1 403 Forbidden");
        if($echo){
            self::SafeEcho($msg);
        }
        self::End(
            '<h1>403 Forbidden [Server]</h1>'
            ,true);
    }

    /**
     * @param string $msg
     * @param bool $close
     */
    public static function End($msg = '',$close = false){
        if($close){
            $_SERVER['HTTP_CONNECTION'] = 'close';
        }
        \Workerman\Protocols\Http::end($msg);
    }

    /**
     * 关闭连接
     */
    public static function Close(){
        $_SERVER['HTTP_CONNECTION'] = 'close';
    }


    # ******************* Normal tools ******************* #

    /**
     * @param string $msg
     * @param string $tag
     * @return array|mixed
     */
    public static function SafeEcho($msg = '',$tag = '#'){
        if(self::isDebug()){
            if(is_object($msg)){
                if($msg instanceof Exception){
                    $msg = $msg->getMessage() . ':' . $msg->getCode();
                }else{
                    return self::Dump($msg);

                }
            }
            if(is_array($msg) or is_bool($msg)){
                \Workerman\Worker::safeEcho("[{$tag}] ", false);
                return self::Dump($msg);
            }else{
                $msg = (string)$msg;
                \Workerman\Worker::safeEcho("[{$tag}] {$msg}\n", false);
            }
        }
    }

    /**
     * @param $var
     * @param array $moreVars
     * @return array|mixed
     */
    public static function Dump($var, $moreVars = []) {
        \Symfony\Component\VarDumper\VarDumper::dump($var);

        foreach ($moreVars as $var) {
            \Symfony\Component\VarDumper\VarDumper::dump($var);
        }

        if (1 < func_num_args()) {
            return func_get_args();
        }

        return $var;
    }

    public static function CamelToLower(string $str) : string {
        return strtolower(trim(preg_replace('/[A-Z]/', '_\\0', $str), '_'));
    }

    public static function LowerToCamel(string $str) : string {
        return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function ($match) {
            return strtoupper($match[1]);
        }, $str));
    }

    public static function ArrayToXml($arr) {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $xml .= "<" . $key . ">" . array2xml($val) . "</" . $key . ">";
            } elseif (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }
}