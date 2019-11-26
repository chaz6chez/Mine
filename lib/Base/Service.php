<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/19           #
# -------------------------- #
namespace Mine\Base;

use Mine\Core\Config;
use Mine\Core\Instance;
use Mine\Core\Response;
use Mine\Definition\Define;
use Mine\Helper\Arr;

class Service extends Instance {

    /**
     * @var Response
     */
    private $_response;

    /**
     * 载入配置内容
     */
    protected function _initConfig(){
        $config = Config::get(Define::CONFIG_SERVICE);
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

    /**
     * 响应
     * @param $response
     * @return Response
     */
    protected function response($response = []) {
        $this->_response = new Response($response);
        return $this->_response;
    }
}