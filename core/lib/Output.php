<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/18           #
# -------------------------- #
namespace core\lib;

class Output {
    private $pattern = 'json';
    private $_cross = false;
    public $_apiRequestId = false;
    public $_end = false;
    private static $_data = [
        'status'  => 1,
        'code'    => 0,
        'msg'     => '',
        'data'    => '',
    ];

    /**
     * @param $pattern
     * @return $this
     */
    public function setPattern($pattern) {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * @param $end
     * @return $this
     */
    public function end($end){
        $this->_end = is_bool($end) ? $end : false;
        return $this;
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
            return $this->output($code, 'System is busy', $data);
        } else {
            if (is_array($msg) && !empty($msg['code'])) {
                $code = $msg['code'];
                $data = $msg['data'];
                $msg = $msg['msg'];
            }
            $err = explode("|", $msg);
            if (is_array($err) && count($err) > 1) {
                $msg = $err[1];
                $code = $err[0];
            }
            return $this->output($code, $msg, $data);
        }
    }

    /**
     * @param $languageCode
     * @param string $data
     * @return array
     */
    public function ecode($languageCode,$data = ''){
        return $this->output($languageCode, 'error', $data);
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
     * @return $this
     */
    public function cross(){
        $this->_cross = true;
        return $this;
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
            $this->pattern == 'html' or
            $this->pattern == 'xml'
        ) {
            wm_header('Content-Type: text/html;charset=utf-8');
        }else{
            wm_header('Content-Type: application/json;charset=utf-8');
        }
        if ($this->_cross){
            wm_header('Access-Control-Allow-Origin: *');
            wm_header('Access-Control-Allow-Method:POST,GET,PUT,OPTION');
            wm_header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
        }
        $status = !empty($code) ? 0 : 1;
        $data = $data === null ? '' : $data;
        $msg = $msg === null ? '' : $msg;
        $code = $code === null ? '' : $code;
        $code = (string)$code;
        $json = array_merge(self::$_data, compact('status','code', 'msg', 'data', 'timestamp'));
        switch ($this->pattern) {
            case 'arr':
                return $json;
                break;
            case 'xml':
                echo array2xml($json);
                break;
            case 'html':
                echo $msg;
                break;
            default:
                echo json_encode($json, JSON_UNESCAPED_UNICODE);
                break;
        }
        # 记录接口输出内容
        if ($this->_apiRequestId) {
            //todo 接口数据默认给记录
        }
        # debug
        if (
            defined('DEBUG') and
            DEBUG
        ){
            cli_echo($json,'RESPONSE');
        }
        # worker man 主动断开连接
        if($this->_end){
            wm_close();
        }
        wm_end();
        return [];
    }
}