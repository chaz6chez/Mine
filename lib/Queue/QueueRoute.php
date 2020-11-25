<?php
namespace Mine\Queue;

use Mine\Core\Instance;

abstract class QueueRoute extends Instance {

    const ENTRANCE = 'entrance';

    protected function _initConfig() {}

    protected function _init() {
        parent::_init();
        QueueBaseLib::ext();
    }

    abstract function entrance(\AMQPEnvelope $even, \AMQPQueue $queue);
}