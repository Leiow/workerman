<?php
require realpath(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use Workerman\Worker;

$worker = new Worker('tcp://0.0.0.0:10000');
$worker->name = 'Tcp Server';
$worker->count = 1;
$worker->onConnect = function($conn) {
    echo $conn->getRemoteIp(), ' connect', PHP_EOL;
};
$worker->onMessage = function($conn, $msg) {
    global $worker;
    echo $worker->name, ' received : ', $msg, PHP_EOL;
    // 服务端接收数据后可向客户端发送结果
    $conn->send('This is server, received data is ' . $msg);
};
$worker->onError = function($conn, $code, $msg) {
    global $worker;
    echo $worker->name, ' error code : ', $code, '; message : ', $msg, PHP_EOL;
};
$worker->onClose = function($conn) {
    global $worker;
    echo $worker->name, ' : client close!', $conn->getRemoteIp(), PHP_EOL;
};
Worker::$stdoutFile = realpath(__DIR__) . '/server.log';
Worker::runAll();