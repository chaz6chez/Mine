<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2019/12/2            #
# -------------------------- #
namespace Mine\Helper;

class SnowFlake {
    const TWEPOCH = 1288834974657; // 时间起始标记点，作为基准，一般取系统的最近时间（一旦确定不能变动）

    const WORKER_ID_BITS     = 5; // 机器标识位数
    const DATA_CENTER_ID_BITS = 5; // 数据中心标识位数
    const SEQUENCE_BITS      = 12; // 毫秒内自增位

    private $worker_id; // 工作机器ID(0~31)
    private $data_center_id; // 数据中心ID(0~31)
    private $sequence; // 毫秒内序列(0~4095)

    private $max_worker_id      = -1 ^ (-1 << self::WORKER_ID_BITS); // 机器ID最大值31
    private $max_data_center_id = -1 ^ (-1 << self::DATA_CENTER_ID_BITS); // 数据中心ID最大值31

    private $worker_id_shift      = self::SEQUENCE_BITS; // 机器ID偏左移12位
    private $data_center_id_shift = self::SEQUENCE_BITS + self::WORKER_ID_BITS; // 数据中心ID左移17位
    private $timestamp_left_shift = self::SEQUENCE_BITS + self::WORKER_ID_BITS + self::DATA_CENTER_ID_BITS; // 时间毫秒左移22位
    private $sequence_mask        = -1 ^ (-1 << self::SEQUENCE_BITS); // 生成序列的掩码4095

    private $last_timestamp = -1; // 上次生产id时间戳

    /**
     * SnowFlake constructor.
     * @param $worker_id
     * @param $data_center_id
     * @param int $sequence
     * @throws Exception
     */
    public function __construct($worker_id, $data_center_id, $sequence = 0) {
        if(!extension_loaded('gmp')){
            throw new Exception('GMP Extension Not Found');
        }
        if (
            $worker_id > $this->max_worker_id or
            $worker_id < 0
        ) {
            throw new Exception("worker Id can't be greater than {$this->max_worker_id} or less than 0");
        }

        if (
            $data_center_id > $this->max_data_center_id or
            $data_center_id < 0
        ) {
            throw new Exception("datacenter Id can't be greater than {$this->max_data_center_id} or less than 0");
        }

        $this->worker_id      = $worker_id;
        $this->data_center_id = $data_center_id;
        $this->sequence       = $sequence;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function nextId() {
        $timestamp = $this->_timeGen();

        if ($timestamp < $this->last_timestamp) {
            $diff_timestamp = bcsub($this->last_timestamp, $timestamp);
            throw new Exception("Clock moved backwards.  Refusing to generate id for {$diff_timestamp} milliseconds");
        }

        if ($this->last_timestamp == $timestamp) {
            $this->sequence = ($this->sequence + 1) & $this->sequence_mask;

            if (0 == $this->sequence) {
                $timestamp = $this->_tilNextMillis($this->last_timestamp);
            }
        } else {
            $this->sequence = 0;
        }

        $this->last_timestamp = $timestamp;

        $gmpTimestamp    = gmp_init($this->_leftShift(bcsub($timestamp, self::TWEPOCH), $this->timestamp_left_shift));
        $gmpDataCenterId = gmp_init($this->_leftShift($this->datacenterId, $this->data_center_id_shift));
        $gmpWorkerId     = gmp_init($this->_leftShift($this->workerId, $this->worker_id_shift));
        $gmpSequence     = gmp_init($this->sequence);

        return gmp_strval(gmp_or(gmp_or(gmp_or($gmpTimestamp, $gmpDataCenterId), $gmpWorkerId), $gmpSequence));
    }

    /**
     * @param $last_timestamp
     * @return float
     */
    protected function _tilNextMillis($last_timestamp) {
        $timestamp = $this->timeGen();
        while ($timestamp <= $last_timestamp) {
            $timestamp = $this->timeGen();
        }

        return $timestamp;
    }

    /**
     * @return float
     */
    protected function _timeGen() {
        return floor(microtime(true) * 1000);
    }

    /**
     * @param $a
     * @param $b
     * @return string
     */
    protected function _leftShift($a, $b) {
        return bcmul($a, bcpow(2, $b));
    }
}