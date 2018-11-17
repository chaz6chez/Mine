<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/18           #
# -------------------------- #
namespace core\lib;

class Output {
    private $pattern = 'json';
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
     * @param int $code
     * @param string $data
     * @return array|mixed
     */
    public function error($msg = '', $code = 4004, $data = '') {
        if (!$msg) {
            $this->output($code, '系统繁忙,请重试', $data);
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
     * 成功
     * @param string $data
     * @param string $msg
     * @return array|mixed
     */
    public function success($data = '',$msg = 'success') {
        return $this->output(0, $msg, $data);
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
        if ($this->pattern != 'arr') {
            wm_header('Content-Type: application/json;charset=utf-8');
        }
        $status = 1;
        if(!empty($code)){
            $status = 0;
        }
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
        # debug 下的控制台输出
        if(defined('DEBUG') and DEBUG){
            dump($json);
        }
        # worker man 主动断开连接
        if($this->_end){
            wm_close();
        }
        wm_end();
    }
}