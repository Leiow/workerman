<?php

use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer;

$worker = new Worker();
$worker->name = $config['tcp']['name'];
$worker->count = $config['tcp']['process'];
$worker->onWorkerStart = function() {
    $client = new AsyncTcpConnection('tcp://127.0.0.1:10000');
    $client->onConnect = function($conn) {
        Timer::add(1, function() use ($conn) {
            $conn->send(date('H:i:s', time()));
        });
    };
    $client->onMessage = function($conn, $msg) {
        echo 'Received: ', $msg, PHP_EOL;
    };
    $client->onError = function($conn, $code, $msg) {
        echo 'Error code:', $code, '; Message : ', $msg, PHP_EOL;
    };
    $client->connect();
};