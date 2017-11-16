<?php
/**
 * 进程间通信
 * 1. 在主进程（入口文件中先实例化 Channel\Server 类）
 * 2. 在其他进程中，可以通过连接 Server，来向指定进程发送消息
 */
require realpath(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use Workerman\Worker;
use Workerman\Lib\Timer;

$channel_server = new Channel\Server('0.0.0.0', 10003);

$worker = new Worker();
$worker->onWorkerStart = function($worker) {
    Timer::add(1, function() {
        echo 'worker send at ', date('H:i:s', time()), PHP_EOL;
        Channel\Client::connect('127.0.0.1', 10003);
        Channel\Client::publish('test', 'hello');
    });
};

$test = new Worker();
$test->onWorkerStart = function($test) {
    Channel\Client::connect('127.0.0.1', 10003);
    Channel\Client::on('test', function($msg) {
        echo 'test received : ', $msg, PHP_EOL;
        echo 'test send at ', date('H:i:s', time()), PHP_EOL;
        Channel\Client::publish('work', 'go to work!');
    });
};

$to_work = new Worker();
$to_work ->onWorkerStart = function($to_work) {
    Channel\Client::connect('127.0.0.1', 10003);
    Channel\Client::on('work', function($msg) {
        echo 'work received : ', $msg, PHP_EOL;
    });
};
Worker::runAll();