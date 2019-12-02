<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/19            #
# -------------------------- #
namespace Mine\Core;

use Mine\Helper\Tools;

final class Autoload{

    private static $_instance;

    /**
     * Autoload constructor.
     */
    final public function __construct(){}

    /**
     * 单例
     * @return Autoload
     */
    final public static function instance(){
        if(!self::$_instance or !self::$_instance instanceof Autoload){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 注册
     */
    public function register(){
        spl_autoload_register([$this, 'autoload']);
    }

    /**
     * 加载
     * @param $className
     */
    public function autoload($className){
        Tools::Http404("Class {$className} Not Found");
    }
}