<?php
require realpath(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use Workerman\Worker;
use Workerman\Lib\Timer;

$pub_worker = new Worker();
$pub_worker->name = 'Publish';
$pub_worker->onWorkerStart = function($pub_worker) {
    $loop = React\EventLoop\Factory::create();
    $context = new React\ZMQ\Context($loop);
    $pub = $context->getSocket(ZMQ::SOCKET_PUB);
    $pub->bind('tcp://127.0.0.1:10005');
    $i = 0;
    $loop->addPeriodicTimer(1, function() use (&$i, $pub) {
        $i++;
        echo 'Publish to sub1 : ', $i, PHP_EOL;
        $pub->send('sub1 '.$i);
    });
    $loop->addPeriodicTimer(3, function() use ($pub) {
        $data = date('H:i:s', time());
        echo 'Publish to sub2 : ', $data, PHP_EOL;
        $pub->send('sub2 ' . $data);
    });
    $loop->run();
};

$sub1_worker = new Worker();
$sub1_worker->name = 'Sub1';
$sub1_worker->onWorkerStart = function($sub1_worker) {
    $loop = React\EventLoop\Factory::create();
    $context = new React\ZMQ\Context($loop);
    $sub = $context->getSocket(ZMQ::SOCKET_SUB);
    $sub->connect('tcp://127.0.0.1:10005');
    $sub->subscribe('sub1');
    $sub->on('message', function($msg) {
        echo 'Sub 1 received : ', $msg, PHP_EOL;
    });
    $loop->run();
};

$sub2_worker = new Worker();
$sub2_worker->name = 'Sub2';
$sub2_worker->onWorkerStart = function($sub2_worker) {
    $loop = React\EventLoop\Factory::create();
    $context = new React\ZMQ\Context($loop);
    $sub = $context->getSocket(ZMQ::SOCKET_SUB);
    $sub->connect('tcp://127.0.0.1:10005');
    $sub->subscribe('sub2');
    $sub->on('message', function($msg) {
        echo 'Sub 2 received : ', $msg, PHP_EOL;
    });
    $loop->run();
};
Worker::runAll();