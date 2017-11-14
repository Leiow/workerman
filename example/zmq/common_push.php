<?php
require realpath(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use Workerman\Worker;
use Workerman\Lib\Timer;

$worker = new Worker();
$worker->name = 'ZMQ PUSH';
$worker->onWorkerStart = function() {
    $loop = Worker::getEventLoop();
    $context = new React\ZMQ\Context($loop);
    $socket = $context->getSocket(ZMQ::SOCKET_PUSH);
    $socket->connect('tcp://127.0.0.1:10001');
    Timer::add(1, function() use ($socket) {
        $socket->send(date('H:i:s', time()));
    });
    $loop->run();
};
Worker::runAll();