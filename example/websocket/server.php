<?php
require realpath(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use Workerman\Worker;

$worker = new Worker('websocket://0.0.0.0:10002');
$worker->name = 'Websocket Server';
$worker->onConnect = function($conn) {
    echo $conn->getRemoteIp(), ' connect!', PHP_EOL;
    $conn->send('hello ' . $conn->getRemoteIp());
};
$worker->onMessage = function($conn, $msg) {
    echo 'Received : ', $msg, PHP_EOL;
    $conn->send('server received!!!!');
};
$worker->onError = function($conn, $code, $msg) {
    echo 'Error code : ', $code, '; message : ', $msg, PHP_EOL;
};
$worker->onClose = function($conn) {
    echo $conn->getRemoteIp(), ' close...', PHP_EOL;
};
Worker::runAll();