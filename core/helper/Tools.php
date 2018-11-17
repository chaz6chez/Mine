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
     * 判断是否是被踢出
     * @param \PDOException $e
     * @return bool
     */
    public static function isGoneAwayError(\PDOException $e) {
        return ($e->errorInfo[1] == 2006 or $e->errorInfo[1] == 2013);
    }

}