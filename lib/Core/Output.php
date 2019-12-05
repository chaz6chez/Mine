<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/18           #
# -------------------------- #
namespace Mine\Core;

use Mine\Definition\Define;
use Mine\Helper\Tools;

class Output {
    const TYPE_HTTP  = Define::OUTPUT_TYPE_HTTP;
    const TYPE_JSON  = Define::OUTPUT_TYPE_JSON;
    const TYPE_ARRAY = Define::OUTPUT_TYPE_ARRAY;
    const TYPE_XML   = Define::OUTPUT_TYPE_XML;
    const TYPE_HTML  = Define::OUTPUT_TYPE_HTML;
    const TYPE_OBJ   = Define::OUTPUT_TYPE_OBJ;

    private $_pattern      = Define::OUTPUT_TYPE_JSON;
    private $_cross        = false;
    private $_end          = false;
    private $_allowHeaders = [
        'Origin',
        'X-Requested-With',
        'Content-Type, Accept'
    ];

    public $status = 1;
    public $code   = 0;
    public $msg    = '';
    public $data   = '';

    protected $_json = '';

    public function _clean(){
        $this->status = 1;
        $this->code   = 0;
        $this->msg    = '';
        $this->data   = '';
        $this->_json  = '';
    }

    /**
     * 反射获取对象属性
     * @return array
     */
    public function getFields() {
        try{
            $class = new \ReflectionClass($this);
            $public = $class->getProperties(\ReflectionProperty::IS_PUBLIC);

            $res = [];
            foreach ($public as $item){
                $name = $item->getName();
                $res[$name] = $this->$name;
            }

        }catch (\Exception $exception){
            $res = '';
        }
        $this->_json = json_encode($res,JSON_UNESCAPED_UNICODE);
        return $res;
    }

    /**
     * @param $pattern
     * @return $this
     */
    public function setPattern(string $pattern) {
        $this->_pattern = $pattern;
        return $this;
    }

    /**
     * @return string
     */
    public function getPattern() : string {
        return $this->_pattern;
    }

    /**
     * @param bool $end
     * @return $this
     */
    public function setEnd(bool $end){
        $this->_end = $end;
        return $this;
    }

    /**
     * @return bool
     */
    public function getEnd() : bool {
        return $this->_end;
    }

    /**
     * @param bool $cross
     * @return $this
     */
    public function setCross(bool $cross = true){
        $this->_cross = $cross;
        return $this;
    }

    /**
     * @return bool
     */
    public function getCross() : bool {
        return $this->_cross;
    }

    /**
     * @param array $allowHeaders
     */
    public function setAllowHeaders(array $allowHeaders){
        if($allowHeaders){
            $this->_allowHeaders = array_merge($this->_allowHeaders, $allowHeaders);
        }
    }

    /**
     * @return array
     */
    public function getAllowHeaders() : array {
        return $this->_allowHeaders;
    }

    /**
     * 输出
     * @param callable|null $sign
     * @return array
     */
    public function output(callable $sign = null) {
        $timestamp = isset($GLOBALS['NOW_TIME']) ? $GLOBALS['NOW_TIME'] : time();
        Tools::Header("TIMESTAMP: {$timestamp}");
        if (
            $this->_pattern == Define::OUTPUT_TYPE_HTML or
            $this->_pattern == Define::OUTPUT_TYPE_XML
        ) {
            Tools::Header('Content-Type: text/html;charset=utf-8');
        }else{
            Tools::Header('Content-Type: application/json;charset=utf-8');
        }
        if ($this->_cross){
            Tools::Header('Access-Control-Allow-Origin: *');
            Tools::Header('Access-Control-Allow-Method:POST,GET,PUT,OPTION');
            if($this->_allowHeaders){
                $headers = implode($this->_allowHeaders,',');
                Tools::Header("Access-Control-Allow-Headers: {$headers}");
            }
            $this->_cross = false;
        }
        $this->status = !empty($this->code) ? 0 : 1;
        $array = $this->getFields();
        $this->_clean();
        if($sign){
            $sign = call_user_func($sign);
            Tools::Header("SIGN: {$sign}");
        }
        switch ($this->_pattern) {
            case self::TYPE_ARRAY:
                return $array;
                break;
            case self::TYPE_XML:
                echo Tools::ArrayToXml($array);
                break;
            case self::TYPE_HTML:
                echo $this->msg;
                break;
            case self::TYPE_HTTP:
                if(!$this->status){
                    Tools::Header("HTTP/1.1 500 Internal Server Error");
                }
                echo $this->_json;
                break;
            default:
                echo $this->_json;
                break;
        }
        # debug
        Tools::SafeEcho($array,'RESPONSE');
        # worker man 主动断开连接
        if($this->_end){
            Tools::Close();
        }
        Tools::End();
        return [];
    }

    /**
     * 成功
     * @param string $data
     * @param string $msg
     * @return array
     */
    public function success($data = '',$msg = 'success') {
        $this->code = (string)'0';
        $this->msg  = (string)$msg;
        $this->data = $data;
        return $this->output();
    }

    /**
     * 失败
     * @param string $msg
     * @param string $code
     * @param string $data
     * @return array
     */
    public function error($msg = '', $code = '500', $data = '') {
        if (!$msg) {
            $code = $this->_pattern == self::TYPE_HTTP ? 'HTTP_500' : $code;
            $msg = 'System is busy';
        }
        if (is_array($msg) && !empty($msg['code'])) {
            $code = $msg['code'];
            $data = $msg['data'];
            $msg = $msg['msg'];
        }
        list($bool,$err) = $this->parse($msg);
        if ($bool) {
            $msg = $err[1];
            $code = $err[0];
        }
        if($this->_pattern == self::TYPE_HTTP){
            $msg = "{$msg}[$code]";
            $code = 'HTTP_500';
        }
        $this->code = (string)$code;
        $this->msg  = (string)$msg;
        $this->data = $data;
        return $this->output();
    }

    /**
     * @param string $msg
     * @return array
     */
    public function parse(string $msg){
        $msg = explode('|', $msg);
        if (
            is_array($msg) and
            count($msg) > 1
        ) {
            return [true,$msg];
        }
        return [false,$msg];
    }
}