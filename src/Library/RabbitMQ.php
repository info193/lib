<?php
/**
 * Created by vim.
 * User: huguopeng
 * Date: 2022/10/28
 * Time: 09:09:02
 * By: RabbitMQ.php
 */
namespace lumenFrame\Library;

use common\Errno;
use frame\Exceptions\BaseException;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMQ
{
    private $_connection;
    private $_channel;
    private $_pushDelay = 0;
    private $_delayQueueName = '';
    private $_retry = 0; // 重试次数
    private $_accrualRetry = 0; // 累加次数
    private $_retryCnf = []; // 重试队列配置
    public $exchange = 'default';
    public $queueName = 'default';
    public $exchangeType = 'direct'; // 交换机类型


    public function __construct()
    {
        $config = [
            'host' => env('RABBITMQ_HOST', '127.0.0.1'),
            'port' => env('RABBITMQ_PORT', 5672),
            'user' => env('RABBITMQ_USER', 'test'),
            'password' => env('RABBITMQ_PASSWORD', 'test'),
            'vhost' => env('RABBITMQ_VHOST', '/'),
        ];
        $this->_connection = new AMQPStreamConnection($config["host"], $config["port"], $config["user"], $config["password"], $config["vhost"]);
        $this->_channel = $this->_connection->channel();
    }

    /**
     * 延迟时长(秒)
     * @param $value
     * @return $this
     */
    public function delay($value = 0)
    {
        $this->_pushDelay = $value;
        return $this;
    }

    /**
     * 创建队列
     * @return string
     */
    private function _createQueue()
    {
        $routeKey = $this->queueName;
        // 延迟队列
        if ($this->_pushDelay != 0) {
            $ttl = 1000 * $this->_pushDelay;
            $this->_delayQueueName = 'enqueue.' . $this->queueName . '.' . $ttl . '.x.delay';
            $args = new AMQPTable([
                'x-dead-letter-exchange' => $this->exchange,
                'x-message-ttl' => $ttl, //消息存活时间
                'x-dead-letter-routing-key' => $this->_delayQueueName
            ]);
            //        //声明一个队列
            /*
             * 创建队列(Queue)
             * name: hello         // 队列名称
             * passive: false      // 如果设置true存在则返回OK，否则就报错。设置false存在返回OK，不存在则自动创建
             * durable: true       // 是否持久化，设置false是存放到内存中RabbitMQ重启后会丢失,
             *                        设置true则代表是一个持久的队列，服务重启之后也会存在，因为服务会把持久化的Queue存放在硬盘上，当服务重启的时候，会重新加载之前被持久化的Queue
             * exclusive: false    // 是否排他，指定该选项为true则队列只对当前连接有效，连接断开后自动删除
             * auto_delete: false // 是否自动删除，当最后一个消费者断开连接之后队列是否自动被删除
             * nowait:false
             * arguments： // 设置AMQPTable，延迟队列，交换机，延迟时间，延迟路由等
             */
            $this->_channel->queue_declare($this->_delayQueueName, false, true, false, false, false, $args);
            $routeKey = $this->_delayQueueName;
        }
        // 重试队列
        if ($this->_accrualRetry != 0 && $this->_retryCnf && $this->_retryCnf[$this->_accrualRetry] != 0) {
            $ttl = 1000 * $this->_retryCnf[$this->_accrualRetry];
            $this->_delayQueueName = 'enqueue.' . $this->queueName . '.' . $ttl . '.x.retry';
            $args = new AMQPTable([
                'x-dead-letter-exchange' => $this->exchange,
                'x-message-ttl' => $ttl, //消息存活时间
                'x-dead-letter-routing-key' => $this->_delayQueueName
            ]);
            /*
             * 创建队列(Queue)
             * name: hello         // 队列名称
             * passive: false      // 如果设置true存在则返回OK，否则就报错。设置false存在返回OK，不存在则自动创建
             * durable: true       // 是否持久化，设置false是存放到内存中RabbitMQ重启后会丢失,
             *                        设置true则代表是一个持久的队列，服务重启之后也会存在，因为服务会把持久化的Queue存放在硬盘上，当服务重启的时候，会重新加载之前被持久化的Queue
             * exclusive: false    // 是否排他，指定该选项为true则队列只对当前连接有效，连接断开后自动删除
             * auto_delete: false // 是否自动删除，当最后一个消费者断开连接之后队列是否自动被删除
             * nowait:false
             * arguments： // 设置AMQPTable，延迟队列，交换机，延迟时间，延迟路由等
             */
            $this->_channel->queue_declare($this->_delayQueueName, false, true, false, false, false, $args);
            $routeKey = $this->_delayQueueName;
        }

        /*
         * 创建交换机(Exchange)
         * name: vckai_exchange// 交换机名称
         * type: direct        // 交换机类型，分别为direct/fanout/topic，参考另外文章的Exchange Type说明。
         * passive: false      // 如果设置true存在则返回OK，否则就报错。设置false存在返回OK，不存在则自动创建
         * durable: false      // 是否持久化，设置false是存放到内存中的，RabbitMQ重启后会丢失
         * auto_delete: false  // 是否自动删除，当最后一个消费者断开连接之后队列是否自动被删除
         */
        //绑定死信queue
        $this->_channel->exchange_declare($this->exchange, $this->exchangeType, false, true, false);
        $this->_channel->queue_declare($this->queueName, false, true, false, false);
        /*
         * 绑定队列和交换机
         * @param string $queue 队列名称
         * @param string $exchange  交换器名称
         * @param string $routing_key   路由key
         * @param bool $nowait
         * @param array $arguments
         * @param int|null $ticket
         * @throws \PhpAmqpLib\Exception\AMQPTimeoutException if the specified operation timeout was exceeded
         * @return mixed|null
         */
        $this->_channel->queue_bind($this->queueName, $this->exchange, $this->_delayQueueName);
        return $routeKey;
    }

    /**
     * 获取job 队列配置
     * @param $jobNamespace
     * @return false|void
     */
    private function _jobConfig($jobNamespace = '')
    {
        if ($jobNamespace == '') {
            Log::error('job 队列名称不存在');
            throw new BaseException(Errno::SERVER_REQUEST_ROUTING_EXISTS_FAIL);
        }
        $jobNamespace = explode('\\', trim($jobNamespace));
        $jobName = lcfirst(end($jobNamespace));
        $jobCnf = config('queue.customJobs');
        if (!isset($jobCnf[$jobName])) {
            Log::error('job 配置文件未找到');
            throw new BaseException(Errno::SERVER_REQUEST_ROUTING_EXISTS_FAIL);
        }
        if (!isset($jobCnf[$jobName]['exchange']) || !$jobCnf[$jobName]['exchange']) {
            Log::error('job队列配置文件未设置交换机名称');
            throw new BaseException(Errno::SERVER_REQUEST_ROUTING_EXISTS_FAIL);
        }
        if (!isset($jobCnf[$jobName]['queueName']) || !$jobCnf[$jobName]['queueName']) {
            Log::error('job队列配置文件未设置队列名称');
            throw new BaseException(Errno::SERVER_REQUEST_ROUTING_EXISTS_FAIL);
        }
        $this->exchange = $jobCnf[$jobName]['exchange'];
        $this->queueName = $jobCnf[$jobName]['queueName'];
        $this->_retry = isset($jobCnf[$jobName]['retry']) ? $jobCnf[$jobName]['retry'] : 0;
        if (isset($jobCnf[$jobName]['retryDelay']) && $jobCnf[$jobName]['retryDelay']) {
            $this->_retryCnf = $jobCnf[$jobName]['retryDelay'];
        }
        if (isset($jobCnf[$jobName]['exchangeType']) && $jobCnf[$jobName]['exchangeType'] != '') {
            $this->exchangeType = $jobCnf[$jobName]['exchangeType'];
        }
    }

    /**
     * 生产者
     * @param $obj
     * @return void
     */
    public function push($obj = '')
    {
        $this->_jobConfig($obj::className());
        $routeKey = $this->_createQueue();
        $body = new AMQPMessage(json_encode($obj), ['content_type' => 'text/plain', 'application_headers' => new AMQPTable(['retry' => $this->_accrualRetry]), 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        return $this->_channel->basic_publish($body, '', $routeKey);
    }

    /**
     * 消费者
     * @param $obj
     * @return void
     */
    public function consume($obj = '')
    {
        $this->_jobConfig($obj::className());
        $this->_createQueue();

        $callback = function ($message) use ($obj) {
            $body = json_decode($message->body, true);
            foreach ($body as $name => $value) {
                $obj->$name = $value;
            }
            $result = $obj->execute($message);
            if ($result == false) {
                $body = json_decode($message->getBody(), true);
                $headersObject = $message->get_properties()['application_headers'];
                $headersArray = $headersObject->getNativeData();
                // 删除超过重试次数信息
                if ($this->_retry != 0 && isset($headersArray['retry']) && $headersArray['retry'] > $this->_retry) {
                    $message->ack();
                }
                // 重试
                if ($this->_retry != 0 && isset($headersArray['retry']) && $headersArray['retry'] < $this->_retry) {
                    $headersArray['retry']++;
                    $this->_accrualRetry = $headersArray['retry'];
                    $this->push(new $obj($body));
                    $message->ack();
                }
            }
        };
        $this->_channel->basic_consume($this->queueName, '', false, false, false, false, $callback);

        while (count($this->_channel->callbacks)) {
            $this->_channel->wait();
        }
    }

    /**
     * @throws \Exception
     */
    public function __destruct()
    {
        $this->_channel->close();
        $this->_connection->close();
    }


}
