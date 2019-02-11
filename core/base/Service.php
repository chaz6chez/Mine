<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/19           #
# -------------------------- #
namespace core\base;

use core\helper\Arr;
use core\helper\Exception;
use core\lib\Config;
use core\lib\Instance;

class Service extends Instance {

    /**
     * 载入配置内容
     */
    protected function _initConfig(){
        $config = Config::get('service');
        $config = isset($config[self::$_class]) ? $config[self::$_class] : [];
        if (!is_null($this->_config)) {
            $this->_config = Arr::merge($this->_config, $config);
        } else {
            foreach ($config as $k => $c) {
                if (isset($this->$k)) {
                    $this->$k = $c;
                }
            }
        }
    }
}