<?php
require realpath(dirname(__DIR__)) . '/vendor/autoload.php';

use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer;

$worker = new Worker();
$worker->name = 'Tcp Client';
$worker->onWorkerStart = function($worker) {
    $client = new AsyncTcpConnection('tcp://127.0.0.1:10000');
    $client->onConnect = function() use ($client) {
        echo 'Connect server success!', PHP_EOL;
        Timer::add(1, function() use ($client) {
            $client->send(date('H:i:s', time()));
        });
    };
    $client->onMessage = function($conn, $msg) {
        echo 'Received: ', $msg, PHP_EOL;
    };
    $client->onError = function($conn, $code, $msg) {
        echo 'Error code: ', $code, '; Message:', $msg, PHP_EOL;
    };
    $client->onClose = function($conn) {
        echo 'Client close!', PHP_EOL;
    };
    $client->connect();
};
$worker->onError = function($conn, $code, $msg) {
    echo 'Worker error code : ', $code, '; Message : ', $msg, PHP_EOL;
};
Worker::runAll();