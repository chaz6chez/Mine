<?php
namespace Example\Common\Queue;

use Mine\Queue\QueueAbstract;
use Mine\Queue\QueueRoute;

class QueueExample extends QueueRoute {
    protected $_channel_count = 4;
    protected $_queue_name = 'example';
    protected $_exchange_type = QueueAbstract::EXCHANGE_TYPE_DIRECT;
    protected $_exchange_name = 'example';

    public function entrance(\AMQPEnvelope $even, \AMQPQueue $queue) {
        dump($this->getParams());
        return $this->getParams();
    }
}