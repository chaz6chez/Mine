<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/11/6            #
# -------------------------- #
namespace CnukServer;

use CnukServer\Withdrawal\Request as WithdrawalRequest;
use CnukServer\Exchange\Request as ExchangeRequest;
use CnukServer\Deposit\Request as DepositRequest;
use CnukServer\Transfer\Request as TransferRequest;
use CnukServer\C2C\Request as C2CRequest;
use CnukServer\StatisticServer\Request as StatisticServerRequest;
use core\helper\Exception;
use Grpc\BaseStub;

class CnukServerBase extends BaseStub{

    /**
     * CnukServerBase constructor.
     * @param $hostname
     * @param $opts
     * @param null $channel
     * @throws Exception
     */
    public function __construct($hostname, $opts, $channel = null) {
        try{
            parent::__construct($hostname, $opts, $channel);
        }catch (\Exception $e){
            throw new Exception($e);
        }
    }

    /**
     * @param WithdrawalRequest $argument
     * @param array $metadata
     * @param array $options
     * @return \Grpc\UnaryCall
     */
    public function Withdrawal( WithdrawalRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/CnukServer.Route.CnukServices/Withdrawal',
            $argument,
            ['CnukServer\Withdrawal\Response', 'decode'],
            $metadata, $options);
    }

    /**
     * @param WithdrawalRequest $argument
     * @param array $metadata
     * @param array $options
     * @return \Grpc\UnaryCall
     */
    public function ConfirWithdrawal( WithdrawalRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/CnukServer.Route.CnukServices/Withdrawal',
            $argument,
            ['CnukServer\Withdrawal\Response', 'decode'],
            $metadata, $options);
    }

    /**
     * trade V2V V2L  L2L L2V
     * @param ExchangeRequest $argument
     * @param array $metadata
     * @param array $options
     * @return \Grpc\UnaryCall
     */
    public function Trade( ExchangeRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/CnukServer.Route.CnukServices/Exchange',
            $argument,
            ['CnukServer\Exchange\Response', 'decode'],
            $metadata, $options);
    }

    /**
     * V2V  L2L
     * @param TransferRequest $argument
     * @param array $metadata
     * @param array $options
     * @return \Grpc\UnaryCall
     */
    public function Transfer( TransferRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/CnukServer.Route.CnukServices/Transfer',
            $argument,
            ['CnukServer\Transfer\Response', 'decode'],
            $metadata, $options);
    }

    /**
     * V2V
     * @param DepositRequest $argument
     * @param array $metadata
     * @param array $options
     * @return \Grpc\UnaryCall
     */
    public function Deposit( DepositRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/CnukServer.Route.CnukServices/Deposit',
            $argument,
            ['CnukServer\Deposit\Response', 'decode'],
            $metadata, $options);
    }

    /**
     * V2V or V2L
     * @param C2CRequest $argument
     * @param array $metadata
     * @param array $options
     * @return \Grpc\UnaryCall
     */
    public function SubmitC2cOrd( C2CRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/CnukServer.Route.CnukServices/SubmitC2cOrd',
            $argument,
            ['CnukServer\C2C\Response', 'decode'],
            $metadata, $options);
    }

    /**
     * @param C2CRequest $argument
     * @param array $metadata
     * @param array $options
     * @return \Grpc\UnaryCall
     */
    public function ConfirC2cOrd( C2CRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/CnukServer.Route.CnukServices/ConfirC2cOrd',
            $argument,
            ['CnukServer\C2C\Response', 'decode'],
            $metadata, $options);
    }

    /**
     * @param C2CRequest $argument
     * @param array $metadata
     * @param array $options
     * @return \Grpc\UnaryCall
     */
    public function CancleC2cOrd( C2CRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/CnukServer.Route.CnukServices/CancleC2cOrd',
            $argument,
            ['CnukServer\C2C\Response', 'decode'],
            $metadata, $options);
    }

    /**
     * @param StatisticServerRequest $argument
     * @param array $metadata
     * @param array $options
     * @return \Grpc\UnaryCall
     */
    public function StatisticServer( StatisticServerRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/CnukServer.Route.CnukServices/StatisticServer',
            $argument,
            ['CnukServer\StatisticServer\Response', 'decode'],
            $metadata, $options);
    }
}