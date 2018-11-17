<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/18           #
# -------------------------- #
namespace core\lib;

class Result {

    private $_pattern = 'json';
    private $_data = [];
    private $_output = null;

    /**
     * Result constructor.
     * @param $data
     */
    public function __construct($data) {
        if (!isset($data['code']) || !isset($data['msg']) || !isset($data['data'])) {
            wm_500('数据获取失败,格式错误');
        }
        $this->_data = $data;
        $this->_output = new Output();
    }

    /**
     * 设置类型
     * @param $pattern
     * @return $this
     */
    public function setPattern($pattern) {
        $this->_pattern = $pattern;
        $this->_output->setPattern($this->_pattern);
        return $this;
    }

    /**
     * 获取数据结果
     * @param string $key
     * @return array|bool
     */
    public function getData($key = '') {
        if (!empty($this->_data['data'])) {
            if ($key) {
                if (isset($this->_data['data'][$key])) {
                    return $this->_data['data'][$key];
                } else {
                    return false;
                }
            }
            return $this->_data['data'];
        }
        return '';
    }

    /**
     * 获取错误
     * @return $this|bool
     */
    public function hasError() {
        if (!empty($this->_data['code'])) {
            return $this;
        }
        return false;
    }

    /**
     * 输出结果
     * @return array
     */
    public function output() {
        if ($this->_pattern != 'arr') {
            $this->_output->output($this->_data['code'], $this->_data['msg'], $this->_data['data']);
        } else {
            return $this->_output->output($this->_data['code'], $this->_data['msg'], $this->_data['data']);
        }
    }

    /**
     * 获取结果信息
     * @return mixed
     */
    public function getMessage() {
        return $this->_data['msg'];
    }

    /**
     * 获取错误码
     * @return mixed
     */
    public function getCode() {
        return $this->_data['code'];
    }

    /**
     * 抛出异常
     * @param string $msg
     * @param int $code
     * @param string $data
     * @return array|mixed
     */
    public function throwError($msg = '', $code = 0, $data = '') {
        if (empty($this->_data['code'])) {
            return;
        }
        $this->_data['msg'] = $msg ? $msg . ':' . $this->_data['msg'] : $this->_data['msg'];
        $this->_data['code'] = $code ? $code : $this->_data['code'];
        $this->_data['data'] = $data ? $data : $this->_data['data'];
        if ($this->_pattern != 'arr') {
            $this->_output->error($this->_data['msg'], $this->_data['code'], $this->_data['data']);
        } else {
            return $this->_output->error($this->_data['msg'], $this->_data['code'], $this->_data['data']);
        }
    }
}