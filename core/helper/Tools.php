<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/17            #
# -------------------------- #
namespace core\helper;

class Tools{
    /**
     * 判断是否是唯一键重复的错误
     * @param \PDOException $e
     * @return bool
     */
    public static function isDuplicateError(\PDOException $e) {
        return $e->getCode() == 23000;
    }

    /**
     * 判断MYSQL是否是被踢出
     * @param \PDOException $e
     * @return bool
     */
    public static function isGoneAwayError(\PDOException $e) {
        return ($e->errorInfo[1] == 2006 or $e->errorInfo[1] == 2013);
    }

    /**
     * 判断REDIS是否超时
     * @param \RedisException $e
     * @return bool
     */
    public static function isRedisTimeout(\RedisException $e){
        return ($e->getCode() == 10054 or $e->getMessage() == 10054);
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
     * 是否是全局启动
     * @return bool
     */
    public static function isGlobalStart(){
        if (
            defined('GLOBAL_START') and
            GLOBAL_START
        ){
            return true;
        }
        return false;
    }

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
     * WinOs
     * @param bool $exit
     * @return bool
     */
    public static function isWinOs($exit = false){
        if(strpos(strtolower(PHP_OS), 'win') === 0) {
            if($exit){
                exit('please use launcher.bat'.PHP_EOL);
            }
            return true;
        }
        return false;
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
}