<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2019/2/28            #
# -------------------------- #

use core\db\WMConnection;

class WMConnectionPool {

    /**
     * db connection pool
     * @var resource
     */
    public static $_db = null;

    /**
     * db connection pool
     * @var array
     */
    public static $_dbs = [];

    /**
     * db mapping to pool
     */
    public static $_dbsPool = [];

    /**
     * 闲置分配和设置锁
     * @var boolean
     */
    public static $_idleLock = false;

    /**
     * 连接池默认数量
     * @var int
     */
    public static $_defaultDbConnPoolNum = 100;

    /**
     * make a db connection
     */
    public static function initDbConn()
    {
        if (self::$_db === null) {
            self::$_db = self::createDbConn();
        }
        return self::$_db;
    }

    /**
     * Create a new db connection
     * @return WMConnection
     */
    public static function createDbConn()
    {
        $config = $GLOBALS['app_conf']['db'];
        return new WMConnection(
            $config['host'],
            $config['port'],
            $config['username'],
            $config['password'],
            $config['db_name']
        );
    }

    /**
     * Get a DB connection instance
     * @param mixed $useTrans Defaults to false
     * @return object The db connection instance
     */
    public static function getDB($useTrans = false)
    {
        if ($useTrans === false) {
            return self::initDbConn();
        }

        if (!isset(self::$_dbsPool[$useTrans])) {
            $index = self::getIdle($useTrans); // 获取置的连接，如果有，用闲置
            if (
                $index === false or
                !isset(self::$_dbs[$index])
            ) {
                $index = 'dbConn_' . md5(microtime(true) . count(self::$_dbs));
                self::$_dbs[$index] = self::createDbConn();
            }
            self::$_dbsPool[$useTrans] = $index;
        } else {
            $index = self::$_dbsPool[$useTrans];
        }
        return self::$_dbs[$index];
    }

    /**
     * close db conn
     * @param mixed $useTrans defaults to false
     */
    public static function closeDB($useTrans = false)
    {
        if (
            $useTrans !== false and
            isset(self::$_dbsPool[$useTrans])
        ) {
            if (count(self::$_dbs) > self::$_defaultDbConnPoolNum) {
                $index = self::$_dbsPool[$useTrans];
                self::$_dbs[$index]->closeConnection();
                self::$_dbs[$index] = null;
                unset(self::$_dbs[$index]);
                unset(self::$_dbsPool[$useTrans]);
            } else {
                self::setIdle($useTrans); // 将连接设置为闲置
            }
        }
        if ($useTrans === false) {
            self::$_db = null;
        }
    }

    /**
     * 从pool获取一个闲置的连接, 并赋值为指定的连接transToken
     * 找到闲置则返回连接索引，否则返回false
     *
     * @param $transToken
     * @return mixed
     */
    private static function getIdle($transToken)
    {
        if (self::$_idleLock === true) {
            return;
        }
        self::$_idleLock = true;
        foreach (self::$_dbsPool as $key => $item) {
            if (strpos($key, 'idle_') === 0) {
                self::$_dbsPool[$transToken] = self::$_dbsPool[$key];
                unset(self::$_dbsPool[$key]);
                self::$_idleLock = false;
                return self::$_dbsPool[$transToken];
            }
        }
        self::$_idleLock = false;
        return false;
    }

    /**
     * 将一个连接设置为闲置
     * @param $transToken
     */
    private static function setIdle($transToken)
    {
        if (self::$_idleLock === true) {
            return;
        }
        self::$_idleLock = true;
        if (isset(self::$_dbsPool[$transToken])) {
            $key = 'idle_' . md5(microtime(true));
            $tmp = self::$_dbsPool[$transToken];
            unset(self::$_dbsPool[$transToken]);
            self::$_dbsPool[$key] = $tmp;
        }
        self::$_idleLock = false;
    }
}