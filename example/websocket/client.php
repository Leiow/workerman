<?php
require realpath(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer;

$worker = new Worker();
$worker->name = 'Websocket Client';
$worker->onWorkerStart = function($worker) {
    $connect = new AsyncTcpConnection('ws://127.0.0.1:10002');
    $connect->onConnect = function($conn) {
        echo 'client connect success!', PHP_EOL;
        $conn->send(date('H:i:s', time()));
    };
    $connect->onMessage = function($conn, $msg) {
        echo 'Received : ', $msg, PHP_EOL;
    };
    $connect->onError = function($conn, $code, $msg) use ($connect) {
        echo '[Client error]  code : ', $code, '; message : ', $msg, PHP_EOL;
    };
    $connect->onClose = function($conn) use ($connect) {
        echo 'Server close!', PHP_EOL;
        $conn->reConnect(2);
    };
    $connect->connect();
};
$worker->onWorkerStop = function($worker) {
    echo 'Worker stop, waiting for rebuid!', PHP_EOL;
};
Worker::runAll();