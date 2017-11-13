<?php
require realpath(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;

$worker = new Worker();
$worker->name = 'ZMQ PULL';
$worker->onWorkerStart = function() {
    $loop = React\EventLoop\Factory::create();
    $context = new React\ZMQ\Context($loop);
    $socket = $context->getSocket(ZMQ::SOCKET_PULL);
    $socket->bind('tcp://127.0.0.1:10001');
    $socket->on('message', function($msg) {
        echo 'PULL Received : ', $msg, PHP_EOL;
    });
    $loop->run();
};
Worker::runAll();