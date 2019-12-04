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
    private static $_data  = [
        'status'  => 1,
        'code'    => 0,
        'msg'     => '',
        'data'    => '',
    ];

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
     * @param $code
     * @param $msg
     * @param array $data
     * @param int $timestamp
     * @return array
     */
    public function output($code, $msg, $data = [], $timestamp = 0) {
        if(!$timestamp){
            $timestamp = isset($GLOBALS['NOW_TIME']) ? $GLOBALS['NOW_TIME'] : time();
        }
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
        $status = !empty($code) ? 0 : 1;
        $data = $data === null ? '' : $data;
        $msg = $msg === null ? '' : $msg;
        $code = $code === null ? '' : $code;
        $code = (string)$code;
        $json = array_merge(self::$_data, compact('status','code', 'msg', 'data', 'timestamp'));
        switch ($this->_pattern) {
            case self::TYPE_ARRAY:
                return $json;
                break;
            case self::TYPE_XML:
                echo Tools::ArrayToXml($json);
                break;
            case self::TYPE_HTML:
                echo $msg;
                break;
            case self::TYPE_HTTP:
                if(!$status){
                    Tools::Header("HTTP/1.1 500 Internal Server Error");
                }
                echo json_encode($json, JSON_UNESCAPED_UNICODE);
                break;
            default:
                echo json_encode($json, JSON_UNESCAPED_UNICODE);
                break;
        }
        # debug
        Tools::SafeEcho($json,'RESPONSE');
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
        return $this->output('0', $msg, $data);
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
            return $this->output($code, 'System is busy', $data);
        } else {
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
            return $this->output($code, $msg, $data);
        }
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