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
            'username' => 'zbc',
            'password' => 'zbc',
            'tag'      => 'management'
        ],
    ],
    # 队列方法
    'queue' => [
        'queue_server' => [
            'service'         => 'Example\Common\Controller',
            'function'        => 'index',
        ]
    ],
];