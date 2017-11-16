<?php
require realpath(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

use Workerman\Worker;
use Workerman\Lib\Timer;
use Workerman\Connection\AsyncTcpConnection;

$worker = new Worker();
$worker->name = 'Tcp Client';
$worker->count = 2;
$worker->onWorkerStart = function($worker) {
    $client = new AsyncTcpConnection('tcp://127.0.0.1:10000');
    $client->onConnect = function($conn) use ($worker) {
        echo $worker->id, ' connect success!', PHP_EOL;
        $timer = [1, 3];
        Timer::add($timer[$worker->id], function() use ($conn, $worker) {
            $conn->send('This is ' . $worker->id);
        });
    };
    $client->onMessage = function($conn, $msg) use ($worker) {
        echo $worker->id, ' recevied [' . $msg . '] from server', PHP_EOL;
    };
    $client->onError = function($conn, $code, $msg) use ($worker) {
        echo $worker->id, ' error code : ', $code, '; message : ', $msg, PHP_EOL;
    };
    $client->onClose = function($conn) use ($worker) {
        echo $worker->id, ' close!', PHP_EOL;
    };
    $client->connect();
};
Worker::runAll();