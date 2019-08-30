<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: visa_interface.proto

namespace CnukServer\Visa;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>CnukServer.Visa.Response</code>
 */
class Response extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>.CnukServer.Header.Response header = 1;</code>
     */
    private $header = null;
    /**
     * Generated from protobuf field <code>string balance = 2;</code>
     */
    private $balance = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \CnukServer\Header\Response $header
     *     @type string $balance
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\VisaInterface::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.CnukServer.Header.Response header = 1;</code>
     * @return \CnukServer\Header\Response
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Generated from protobuf field <code>.CnukServer.Header.Response header = 1;</code>
     * @param \CnukServer\Header\Response $var
     * @return $this
     */
    public function setHeader($var)
    {
        GPBUtil::checkMessage($var, \CnukServer\Header\Response::class);
        $this->header = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string balance = 2;</code>
     * @return string
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * Generated from protobuf field <code>string balance = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setBalance($var)
    {
        GPBUtil::checkString($var, True);
        $this->balance = $var;

        return $this;
    }

}

