<?php
require realpath(__DIR__) . '/vendor/autoload.php';

use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer;

if (file_exists(realpath(__DIR__) . '/config/config.json')) {
    $config = json_decode(file_get_contents(realpath(__DIR__) . '/config/config.json'), true);
} else {
    exit('Can not find configuration file!');
}

$worker = new Worker();
$worker->name = 'Tcp Client';
$worker->onWorkerStart = function($worker) use ($config) {
    Timer::add(1, function() use ($config) {
    });};
$worker->onError = function($conn, $code, $msg) {
    echo 'Worker error code : ', $code, '; Message : ', $msg, PHP_EOL;
};
Worker::runAll();