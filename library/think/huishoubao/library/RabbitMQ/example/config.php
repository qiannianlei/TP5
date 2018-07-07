<?php
    //这个样例配制,支持DB组, 交换组.   请放到子项目本配制里去
    return [
        //DB配制组
        "db"      => [
            "test" => [
                'host'     => '10.0.30.41',
                'port'     => 5672,
                'username' => 'hsb',
                'password' => 'hsb.com',
                'vhost'    => '/',
            ],
        ],

        //交换
        "channel" => [
            "php_ps" => [
                "channel_type"  => AMQP_EX_TYPE_FANOUT,  //订阅模式或广播  AMQP_EX_TYPE_DIRECT表示生产消费
                "exchange_name" => 'php_test_exchange_001',
                "queue_name"    => 'php_test_queue_001',//队列名称 AMQP_EX_TYPE_FANOUT时,不起作用
                "route_key"     => 'php_test_route_001', //路由名称 AMQP_EX_TYPE_FANOUT时,不起作用
                "durable"       => true, //持久化，默认True
                "autodelete"    => false, //自动删除
                "mirror"        => false //镜像队列，打开后消息会在节点之间复制，有master和slave的概念
            ],
            "php_pp" => [
                "channel_type"  => AMQP_EX_TYPE_DIRECT,  //订阅模式或广播  AMQP_EX_TYPE_DIRECT表示生产消费
                "exchange_name" => 'php_test_exchange_002',
                "queue_name"    => 'php_test_queue_002',//队列名称 AMQP_EX_TYPE_FANOUT时,不起作用
                "route_key"     => 'php_test_route_002', //路由名称 AMQP_EX_TYPE_FANOUT时,不起作用
                "durable"       => true, //持久化，默认True
                "autodelete"    => false, //自动删除
                "mirror"        => false //镜像队列，打开后消息会在节点之间复制，有master和slave的概念
            ],
        ],
    ];