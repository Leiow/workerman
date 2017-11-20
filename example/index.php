<?php
/**
 * 1. Websocket 客户端连接服务端1，发送数据请求；
 * 2. 服务端 1 通过进程间通信，将数据发送到 Inter Serve 进程中进行处理；
 * 3. Inter Serve 处理后，将数据分发到不同消息队列进行处理；
 * 4. 消息队列执行完成后，将执行结果发送到结果推送进程，即 Platform Server；
 * 5. Platform Server 通过 Websocket 发送执行结果。
 * 
 * 使用到了定时任务，Websocket服务端及客户端，消息队列，进程间通信
 */
require realpath(dirname(__DIR__)) . '/vendor/autoload.php';

use Workerman\Worker;
use Workerman\Lib\Timer;
use Workerman\Connection\AsyncTcpConnection;

// Websocket Client
$ws_client = new Worker();
$ws_client->name = 'Websocket Client';
$ws_client->onWorkerStart = function($ws_client) {
    $client = new AsyncTcpConnection('ws://127.0.0.1:10001');
    $client->onConnect = function($conn) {
        Timer::add(2, function() use ($conn) {
            $data = date('H:i:s', time());
            echo 'Websocket Client send : ', $data, PHP_EOL;
            $conn->send($data);
        });
    };
    $client->onMessage = function($conn, $msg) {
        echo 'Websocket Client received : ', $msg, PHP_EOL;
    };
    $client->onError = function($conn, $code, $msg) {
        echo 'Websocket Client error code : ', $code, '; message : ', $msg, PHP_EOL;
    };
    $client->onClose = function() {
        echo 'Websocket Client close!', PHP_EOL;
    };
    $client->connect();
};
$ws_client->onError = function($conn, $code, $msg) {
    echo 'Websocket Client error code : ', $code, '; message : ', $msg, PHP_EOL;
};
$ws_client->onClose = function($conn) {
    echo 'Websocket Client close! Waiting for reconnect ..........', PHP_EOL;
    $conn->reConnect(2);
};

// Channel Server
$channel_server = new Channel\Server('0.0.0.0', 10002);

// Websocket Server, provide interface serve
$ws = new Worker('websocket://0.0.0.0:10001');
$ws->name = 'Websocket Server';
$ws->count = 0;
$ws->onConnect = function($conn) {
    echo $conn->getRemoteIp(), ' connect!', PHP_EOL;
};
$ws->onMessage = function($conn, $msg) {
    echo 'Websocket Server received : ', $msg, PHP_EOL;
    // push data to other process
    Channel\Client::connect('127.0.0.1', 10002);
    Channel\Client::publish('inter_serve', $msg);
    $conn->send(json_encode([
        'result' => 'received'
    ]));
};
$ws->onError = function($conn, $code, $msg) {
    echo 'Websocket Server error code : ', $code, '; message : ', $msg, PHP_EOL;
};
$ws->onClose = function($conn) {
    echo 'Websocket Server close!', PHP_EOL;
};

class ZMQPush
{
    private $socket = null;
    private static $instance = null;
    private $loop;
    private function __construct()
    {
        $this->loop = React\EventLoop\Factory::create();
        $context = new React\ZMQ\Context($this->loop);
        $this->socket = $context->getSocket(ZMQ::SOCKET_PUSH);
        $this->socket->bind('tcp://127.0.0.1:10003');
        return $this->socket;
    }
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function send($data)
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }
        $this->socket->send($data);
        $this->loop->run();
    }
}

// Channel Client, resolve data and push data to ZMQ
$inter_serve = new Worker();
$inter_serve->name = 'Inter Serve';
$inter_serve->onWorkerStart = function() {
    Channel\Client::connect('127.0.0.1', 10002);
    Channel\Client::on('inter_serve', function($msg) {
        echo 'Inter Serve send : ', $msg, PHP_EOL;
        ZMQPush::getInstance()->send('[Inter Serve] - ' . $msg);
    });
};

// data processing, then send the result to the channel
$zmq_puller = new Worker();
$zmq_puller->name = 'ZMQ PULLER';
$zmq_puller->count = 1;
$zmq_puller->onWorkerStart = function($zmq_puller) {
    $loop = Worker::getEventLoop();
    $context = new React\ZMQ\Context($loop);
    $socket = $context->getSocket(ZMQ::SOCKET_PULL);
    $socket->connect('tcp://127.0.0.1:10003');
    $socket->on('message', function($msg) use ($zmq_puller) {
        echo 'ZMQ Puller [' . $zmq_puller->id . '] received : ', $msg, PHP_EOL;
        Channel\Client::connect('127.0.0.1', 10002);
        Channel\Client::publish('platform', 'zmq_puller ' . $msg);
    });
    $loop->run();
};

// platform channel, transmit data to the platform
$platform_channel = new Worker();
$platform_channel->name = 'Platform Channel';
$platform_channel->onWorkerStart = function() {
    $sender = new AsyncTcpConnection('ws://127.0.0.1:10004');
    $sender->onConnect = function($conn) {
        Channel\Client::connect('127.0.0.1', 10002);
        Channel\Client::on('platform', function($msg) use ($conn) {
            $conn->send('platform channel send ' . $msg);
        });
    };
    $sender->onMessage = function($conn, $msg) {
        echo 'Platform Channel received : ', $msg, PHP_EOL;
    };
    $sender->onError = function($conn, $code, $msg) {
        echo 'Platform Channel error code : ', $code, ';message : ', $msg, PHP_EOL;
    };
    $sender->onClose = function($conn) {
        echo 'Platform Channel close! Waiting for reconnect...', PHP_EOL;
        $conn->reConnect(3);
    };
    $sender->connect();
};

// platform server, received the result
$platform = new Worker('websocket://0.0.0.0:10004');
$platform->name = 'Platform Server';
$platform->onConnect = function($conn) {
    echo $conn->getRemoteIp(), ' connected Platform Server', PHP_EOL;
};
$platform->onMessage = function($conn, $msg) {
    echo 'Platform Server received : ', $msg, PHP_EOL;
};
$platform->onError = function($conn, $code, $msg) {
    echo 'Platform Server error code : ', $code, '; message : ', $msg, PHP_EOL;
};
$platform->onClose = function() {
    echo 'Platform Server close ', PHP_EOL;
};

Worker::runAll();