<?php
    //这个订阅发布生产消费样例 - 消费者
    //    error_reporting(0);
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

    class Receive
    {
        function processMessage($envelope, $queue)
        {
            $msg = $envelope->getBody();
            $envelopeID = $envelope->getDeliveryTag();
            $queue->ack($envelopeID);
            $logInfo = "logInfo:" . $msg . '|' . $envelopeID . '' . "\r\n";
            echo $logInfo;
            //$pid = posix_getpid();
            //file_put_contents("log{$pid}.log", $logInfo, FILE_APPEND);
        }
    }

    $receiveClass = new Receive();
    $s = $rabbitCon->run([$receiveClass, 'processMessage'], false);