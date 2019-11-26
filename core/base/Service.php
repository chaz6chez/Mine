<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/19           #
# -------------------------- #
namespace core\base;

use core\helper\Arr;
use core\lib\Config;
use core\lib\Instance;
use core\lib\Output;
use core\lib\Response;
use core\lib\Result;

class Service extends Instance {

    /**
     * @var Output
     */
    private $_output;
    /**
     * @var Result
     */
    private $_result;

    /**
     * @var Response
     */
    private $_response;

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

    /**
     * 获取结果
     * @param $result
     * @return Result
     */
    protected function result($result) {
        $this->_result = new Result($result);
        $this->_result->setPattern('arr');
        return $this->_result;
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

    /**
     * 获取输出器对象
     * @param string $pattern
     * @return array|Output|mixed
     */
    protected function output($pattern = Output::TYPE_ARRAY) {
        if (!$this->_output or !$this->_output instanceof Output) {
            $this->_output = new Output();
        }
        if(is_string($pattern)){
            $this->_output->setPattern($pattern);
        }
        if (is_array($pattern)) {
            $this->_output->setPattern(Output::TYPE_ARRAY);
            if (isset($pattern['errCode']) && isset($pattern['message']) && isset($pattern['data'])) {
                return $this->_output->output($pattern['errCode'], $pattern['message'], $pattern['data']);
            } else {
                return $this->_output->success($pattern);
            }
        }
        return $this->_output;
    }
}