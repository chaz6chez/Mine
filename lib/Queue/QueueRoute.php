<?php
namespace Mine\Queue;

use Mine\Core\Instance;

abstract class QueueRoute extends Instance {
    const ENTRANCE = 'entrance';

    protected $_channel_count = 0;
    protected $_exchange_name;
    protected $_exchange_type;
    protected $_queue_name;

    private $_method = self::ENTRANCE;
    private $_params = [];


    protected function _initConfig() {}

    protected function _init() {
        parent::_init();
        QueueAbstract::ext();
    }

    final public function getChannelCount() : int{
        return (int)$this->_channel_count;
    }

    final public function getExchangeName() : string {
        return (string)$this->_exchange_name;
    }

    final public function getExchangeType() : string {
        return (string)$this->_exchange_type;
    }

    final public function getQueueName() : string {
        return (string)$this->_queue_name;
    }

    final public function getMethod() : string {
        return (string)$this->_method;
    }

    final public function getParams() : array {
        return (array)$this->_params;
    }

    final public function setMethod(string $method){
        $this->_method = $method ? $method : $this->_method;
    }

    final public function setParams(array $params){
        $this->_params = $params;
    }

    final public function verify(string $body) : bool {
        $body = QueueAbstract::decode($body);
        $this->_method = isset($body['method']) ? $body['method'] : null;
        $this->_params = isset($body['params']) ? $body['params'] : [];
        return boolval(method_exists($this, $this->_method));
    }

    /**
     * 入口
     * @param QueueLib $client
     * @return mixed
     */
    abstract function entrance(QueueLib $client);

    /**
     * publish
     * @param QueueLib $client
     * @param array $data
     * @param string $method
     * @return mixed
     */
    abstract function publish(QueueLib $client, array $data, string $method = self::ENTRANCE);

}