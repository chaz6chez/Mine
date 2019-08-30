<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: header.proto

namespace CnukServer\Header;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>CnukServer.Header.Request</code>
 */
class Request extends \Google\Protobuf\Internal\Message
{
    /**
     * the socket url 
     *
     * Generated from protobuf field <code>string url = 1;</code>
     */
    private $url = '';
    /**
     * Generated from protobuf field <code>string signal = 2;</code>
     */
    private $signal = '';
    /**
     * Generated from protobuf field <code>string public_key = 3;</code>
     */
    private $public_key = '';
    /**
     * Generated from protobuf field <code>uint64 timestamp = 4;</code>
     */
    private $timestamp = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $url
     *           the socket url 
     *     @type string $signal
     *     @type string $public_key
     *     @type int|string $timestamp
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Header::initOnce();
        parent::__construct($data);
    }

    /**
     * the socket url 
     *
     * Generated from protobuf field <code>string url = 1;</code>
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * the socket url 
     *
     * Generated from protobuf field <code>string url = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setUrl($var)
    {
        GPBUtil::checkString($var, True);
        $this->url = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string signal = 2;</code>
     * @return string
     */
    public function getSignal()
    {
        return $this->signal;
    }

    /**
     * Generated from protobuf field <code>string signal = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setSignal($var)
    {
        GPBUtil::checkString($var, True);
        $this->signal = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string public_key = 3;</code>
     * @return string
     */
    public function getPublicKey()
    {
        return $this->public_key;
    }

    /**
     * Generated from protobuf field <code>string public_key = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setPublicKey($var)
    {
        GPBUtil::checkString($var, True);
        $this->public_key = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint64 timestamp = 4;</code>
     * @return int|string
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Generated from protobuf field <code>uint64 timestamp = 4;</code>
     * @param int|string $var
     * @return $this
     */
    public function setTimestamp($var)
    {
        GPBUtil::checkUint64($var);
        $this->timestamp = $var;

        return $this;
    }

}

