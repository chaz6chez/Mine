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

}