<?php
require realpath(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer;

$worker = new Worker();
$worker->name = 'Websocket Client';
$worker->onWorkerStart = function($worker) {
    $connect = new AsyncTcpConnection('ws://127.0.0.1:10002');
    // id 的使用，需要引用，否则无法获取或修改
    $id = null;
    $connect->onConnect = function($conn) use (&$id) {
        echo 'client connect success!', PHP_EOL;
        // 获取 Timer 的 ID，用于 onClose 事件中删除
        $id = Timer::add(1, function() use ($conn) {
            $conn->send(date('H:i:s', time()));
        });
        echo 'Timer id : ', $id, PHP_EOL;
    };
    $connect->onMessage = function($conn, $msg) {
        echo 'Received : ', $msg, PHP_EOL;
    };
    $connect->onError = function($conn, $code, $msg) use ($connect) {
        echo '[Client error]  code : ', $code, '; message : ', $msg, PHP_EOL;
    };
    $connect->onClose = function($conn) use ($connect, &$id) {
        echo 'Server close!', PHP_EOL;
        Timer::del($id);
        $conn->reConnect(2);
    };
    $connect->connect();
};
$worker->onWorkerStop = function($worker) {
    echo 'Worker stop, waiting for rebuid!', PHP_EOL;
};
Worker::runAll();