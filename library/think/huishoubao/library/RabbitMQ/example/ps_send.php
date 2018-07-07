<?php
    //这个订阅发布生产消费样例 - 消费者
    //    set_time_limit(0);
    include '../RabbitMQClass.php';

    $configs = include "./config.php";
    $db = empty($configs['db']) ? [] : $configs['db'];
    if (empty($db) || empty($db['test'])) {
        echo "config is error.";
        exit;
    }

    //实列
    $rabbitCon = RabbitMQClass::getInstance($db['test']);

    //rabbitCon
    $channel = $configs['channel'];
    $rabbitCon->setChannelConfig($channel['php_ps']);

    //生产数据
    for ($i = 0; $i <= 100; $i++) {
        $rabbitCon->send(date('Y-m-d H:i:s', time()) . " ({$i})");
    }
    exit();