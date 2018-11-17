<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/19            #
# -------------------------- #
namespace core\lib;

class Autoload{

    /**
     * 注册
     */
    public function register(){
        spl_autoload_register([$this, 'autoload']);
    }

    /**
     * 加载
     * @param $className
     * @throws \Exception
     */
    public function autoload($className){
        wm_404("{$className} was not found");
    }
}