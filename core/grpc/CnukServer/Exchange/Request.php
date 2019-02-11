<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: exchange_interface.proto

namespace CnukServer\Exchange;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>CnukServer.Exchange.Request</code>
 */
class Request extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>.CnukServer.Header.Request header = 1;</code>
     */
    private $header = null;
    /**
     * Generated from protobuf field <code>uint64 time = 2;</code>
     */
    private $time = 0;
    /**
     * Generated from protobuf field <code>string user_id = 3;</code>
     */
    private $user_id = '';
    /**
     * Generated from protobuf field <code>string trade_pair = 5;</code>
     */
    private $trade_pair = '';
    /**
     * Generated from protobuf field <code>string amount = 6;</code>
     */
    private $amount = '';
    /**
     * Generated from protobuf field <code>string rate = 7;</code>
     */
    private $rate = '';
    /**
     * Generated from protobuf field <code>string fee = 8;</code>
     */
    private $fee = '';
    /**
     * Generated from protobuf field <code>string order_number = 9;</code>
     */
    private $order_number = '';
    /**
     * Generated from protobuf field <code>uint64 type = 10;</code>
     */
    private $type = 0;
    /**
     * Generated from protobuf field <code>uint64 order_status = 11;</code>
     */
    private $order_status = 0;
    /**
     * Generated from protobuf field <code>string fee_uid = 12;</code>
     */
    private $fee_uid = '';
    /**
     * Generated from protobuf field <code>string total = 13;</code>
     */
    private $total = '';
    /**
     * Generated from protobuf field <code>uint64 calculate_type = 14;</code>
     */
    private $calculate_type = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \CnukServer\Header\Request $header
     *     @type int|string $time
     *     @type string $user_id
     *     @type string $trade_pair
     *     @type string $amount
     *     @type string $rate
     *     @type string $fee
     *     @type string $order_number
     *     @type int|string $type
     *     @type int|string $order_status
     *     @type string $fee_uid
     *     @type string $total
     *     @type int|string $calculate_type
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\ExchangeInterface::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.CnukServer.Header.Request header = 1;</code>
     * @return \CnukServer\Header\Request
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Generated from protobuf field <code>.CnukServer.Header.Request header = 1;</code>
     * @param \CnukServer\Header\Request $var
     * @return $this
     */
    public function setHeader($var)
    {
        GPBUtil::checkMessage($var, \CnukServer\Header\Request::class);
        $this->header = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint64 time = 2;</code>
     * @return int|string
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Generated from protobuf field <code>uint64 time = 2;</code>
     * @param int|string $var
     * @return $this
     */
    public function setTime($var)
    {
        GPBUtil::checkUint64($var);
        $this->time = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string user_id = 3;</code>
     * @return string
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Generated from protobuf field <code>string user_id = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setUserId($var)
    {
        GPBUtil::checkString($var, True);
        $this->user_id = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string trade_pair = 5;</code>
     * @return string
     */
    public function getTradePair()
    {
        return $this->trade_pair;
    }

    /**
     * Generated from protobuf field <code>string trade_pair = 5;</code>
     * @param string $var
     * @return $this
     */
    public function setTradePair($var)
    {
        GPBUtil::checkString($var, True);
        $this->trade_pair = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string amount = 6;</code>
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Generated from protobuf field <code>string amount = 6;</code>
     * @param string $var
     * @return $this
     */
    public function setAmount($var)
    {
        GPBUtil::checkString($var, True);
        $this->amount = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string rate = 7;</code>
     * @return string
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * Generated from protobuf field <code>string rate = 7;</code>
     * @param string $var
     * @return $this
     */
    public function setRate($var)
    {
        GPBUtil::checkString($var, True);
        $this->rate = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string fee = 8;</code>
     * @return string
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * Generated from protobuf field <code>string fee = 8;</code>
     * @param string $var
     * @return $this
     */
    public function setFee($var)
    {
        GPBUtil::checkString($var, True);
        $this->fee = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string order_number = 9;</code>
     * @return string
     */
    public function getOrderNumber()
    {
        return $this->order_number;
    }

    /**
     * Generated from protobuf field <code>string order_number = 9;</code>
     * @param string $var
     * @return $this
     */
    public function setOrderNumber($var)
    {
        GPBUtil::checkString($var, True);
        $this->order_number = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint64 type = 10;</code>
     * @return int|string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Generated from protobuf field <code>uint64 type = 10;</code>
     * @param int|string $var
     * @return $this
     */
    public function setType($var)
    {
        GPBUtil::checkUint64($var);
        $this->type = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint64 order_status = 11;</code>
     * @return int|string
     */
    public function getOrderStatus()
    {
        return $this->order_status;
    }

    /**
     * Generated from protobuf field <code>uint64 order_status = 11;</code>
     * @param int|string $var
     * @return $this
     */
    public function setOrderStatus($var)
    {
        GPBUtil::checkUint64($var);
        $this->order_status = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string fee_uid = 12;</code>
     * @return string
     */
    public function getFeeUid()
    {
        return $this->fee_uid;
    }

    /**
     * Generated from protobuf field <code>string fee_uid = 12;</code>
     * @param string $var
     * @return $this
     */
    public function setFeeUid($var)
    {
        GPBUtil::checkString($var, True);
        $this->fee_uid = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string total = 13;</code>
     * @return string
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Generated from protobuf field <code>string total = 13;</code>
     * @param string $var
     * @return $this
     */
    public function setTotal($var)
    {
        GPBUtil::checkString($var, True);
        $this->total = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint64 calculate_type = 14;</code>
     * @return int|string
     */
    public function getCalculateType()
    {
        return $this->calculate_type;
    }

    /**
     * Generated from protobuf field <code>uint64 calculate_type = 14;</code>
     * @param int|string $var
     * @return $this
     */
    public function setCalculateType($var)
    {
        GPBUtil::checkUint64($var);
        $this->calculate_type = $var;

        return $this;
    }

}
