<?php
return [
    'service' => [

    ],
    # 队列 (生产者与消费者)
    'mq' => [
        'rabbit' => [
            'host'  => '127.0.0.1',
            'vhost' => '/',
            'port'  => '5672',
            'username' => 'worker',
            'password' => 'worker',
            'tag'      => 'management'
        ],
    ],
    # 队列方法
    'queue' => [
        'queue_server' => [
            'route'         => 'Example\Common\Queue\QueueExample',
            'event_limit'   => 0,
            'interval'      => 0.02
        ]
    ],
];