<?php
    namespace think\huishoubao\library\RabbitMQ;

    /**
     * rabbitMq协议操作类  支持生产消费和发布订阅等几种模式
     * 注意安装 php_amqp扩展
     * Class RabbitMQClass
     * @author  james rh
     */
    class RabbitMQClass
    {
        //单例实列
        private static $_instance = [];

        private $configs = [];

        //交换机名称
        private $exchange_name = '';

        //队列名称
        private $queue_name = '';

        //路由名称
        private $route_key = '';

        //类型 默认 direct,表示生产消费.  AMQP_EX_TYPE_FANOUT 表示广播订阅
        ///默认情况下，RabbitMQ使用名称为“amq.direct”的Direct Exchange，routing_key默认名字与Queue保持一致
        //Direct使用具体的routing_key来投递；
        //Fanout则忽略routing_key，直接广播给所有的Queue；
        //Topic是使用模糊匹配来对一组routing_key进行投递；
        //Headers也是忽略routing_key，使用消息中的Headers信息来投递
        private $channel_type = AMQP_EX_TYPE_DIRECT;

        /*
         * 持久化，默认True
         */
        private $durable = true;

        /*
         * 自动删除
         * exchange is deleted when all queues have finished using it
         * queue is deleted when last consumer unsubscribes
         *
         */
        private $autodelete = false;

        /*
         * 镜像
         * 镜像队列，打开后消息会在节点之间复制，有master和slave的概念
         */
        private $mirror = false;

        private $_conn = null;

        private $_exchange = null;

        private $_channel = null;

        private $_queue = null;

        /**
         * RabbitMQClass constructor.
         * @param array $configs array('host'=>$host,'port'=>5672,'username'=>$username,'password'=>$password,'vhost'=>'/')
         */
        private function __construct($configs = [])
        {
            $this->setConfigs($configs);
        }

        /**
         * 单例
         * @param array $configs
         * @return mixed
         */
        public static function getInstance($configs = [])
        {
            $md5 = md5(serialize($configs));
            if (isset(self::$_instance[$md5])) {
                return self::$_instance[$md5];
            }

            self::$_instance[$md5] = new self($configs);
            return self::$_instance[$md5];
        }

        /**
         * 设置配制
         * @param array $channel exchange_name,queue_name, route_key ,channel_type
         * @param bool $isReset 是否重置,只有当连接时,才可以改变 rabbitmq连接不变  重置交换机，队列，路由等配置
         */
        public function setChannelConfig($channel = [], $isReset = false)
        {
            if (empty($channel)) {
                return null;
            }

            foreach ($channel as $key => $value) {
                if (is_null($value)) {
                    continue;
                }
                $this->$key = $value;
            }

            //重置
            if ($isReset) {
                $this->initConnection();
            }

        }

        /**
         * 实列化配制
         * @param $configs
         * @throws Exception
         */
        private function setConfigs($configs)
        {
            if (!is_array($configs)) {
                throw new Exception('configs is not array');
            }
            if (!($configs['host'] && $configs['port'] && $configs['username'] && $configs['password'])) {
                throw new Exception('configs is empty');
            }
            if (empty($configs['vhost'])) {
                $configs['vhost'] = '/';
            }
            $configs['login'] = $configs['username'];
            unset($configs['username']);
            $this->configs = $configs;
        }

        /*
         * 设置是否持久化，默认为True
         */

        public function setDurable($durable)
        {
            $this->durable = $durable;
        }

        /*
         * 设置是否自动删除
         */

        public function setAutoDelete($autodelete)
        {
            $this->autodelete = $autodelete;
        }

        /*
         * 设置是否镜像
         */
        public function setMirror($mirror)
        {
            $this->mirror = $mirror;
        }

        /*
         * 打开amqp连接
         */

        private function open()
        {
            if (!$this->_conn) {
                try {
                    $this->_conn = new AMQPConnection($this->configs);
                    $this->_conn->connect();
                    $this->initConnection();
                } catch (AMQPConnectionException $ex) {
                    throw new Exception('cannot connection rabbitmq', 500);
                }
            }
        }

        /*
         * 初始化rabbit连接的相关配置
         */
        private function initConnection()
        {
            if (empty($this->exchange_name) || empty($this->queue_name) || empty($this->route_key)) {
                throw new Exception('rabbitmq exchange_name or queue_name or route_key is empty', 500);
            }

            $this->_channel = new AMQPChannel($this->_conn);
            $this->_exchange = new AMQPExchange($this->_channel);
            $this->_exchange->setName($this->exchange_name);

            //默认情况下，RabbitMQ使用名称为“amq.direct”的Direct Exchange，routing_key默认名字与Queue保持一致
            //Direct使用具体的routing_key来投递；
            //Fanout则忽略routing_key，直接广播给所有的Queue；
            //Topic是使用模糊匹配来对一组routing_key进行投递；
            //Headers也是忽略routing_key，使用消息中的Headers信息来投递
            $this->_exchange->setType($this->channel_type);

            if ($this->durable)
                $this->_exchange->setFlags(AMQP_DURABLE);
            if ($this->autodelete)
                $this->_exchange->setFlags(AMQP_AUTODELETE);
            $this->_exchange->declare();

            $this->_queue = new AMQPQueue($this->_channel);

            if ($this->channel_type == AMQP_EX_TYPE_FANOUT) {
                $this->_queue->setFlags(AMQP_EXCLUSIVE);
            } else {
                $this->_queue->setName($this->queue_name);
            }

            if ($this->durable)
                $this->_queue->setFlags(AMQP_DURABLE);
            if ($this->autodelete)
                $this->_queue->setFlags(AMQP_AUTODELETE);
            if ($this->mirror)
                $this->_queue->setArgument('x-ha-policy', 'all');
            $this->_queue->declare();

            if ($this->channel_type == AMQP_EX_TYPE_FANOUT) {
                $this->_queue->bind($this->exchange_name, '');
            } else {
                $this->_queue->bind($this->exchange_name, $this->route_key);
            }
        }

        public function close()
        {
            if ($this->_conn) {
                $this->_conn->disconnect();
            }
        }

        public function __sleep()
        {
            $this->close();
            return array_keys(get_object_vars($this));
        }

        public function __destruct()
        {
            $this->close();
        }

        /*
         * 生产者发送消息
         */
        public function send($msg)
        {
            $this->open();
            if (is_array($msg)) {
                $msg = json_encode($msg);
            } else {
                $msg = trim(strval($msg));
            }
            return $this->_exchange->publish($msg, $this->route_key);
        }

        /*
         * 消费者
         * $fun_name = array($classobj,$function) or function name string
         * $autoack 是否自动应答
         *
         * function processMessage($envelope, $queue) {
                $msg = $envelope->getBody();
                echo $msg."\n"; //处理消息
                $queue->ack($envelope->getDeliveryTag());//手动应答
            }
         */
        public function run($fun_name, $autoack = true)
        {
            $this->open();
            if (!$fun_name || !$this->_queue) return false;
            while (true) {
                if ($autoack) $this->_queue->consume($fun_name, AMQP_AUTOACK);
                else $this->_queue->consume($fun_name);
            }
        }

    }