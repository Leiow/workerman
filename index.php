<?php
require 'vendor/autoload.php';

use Workerman\Worker;

$worker_path = realpath(__DIR__);
if (file_exists($worker_path . '/config/config.json')) {
    $config = json_decode(file_get_contents($worker_path . '/config/config.json'), true);
} else {
    exit('Can not find the configuration file!');
}

function record(\Workerman\Worker $worker, $msg)
{
    if (Worker::$daemonize) {
        Libs\Record::log($worker, $msg);
    } else {
        echo $msg, PHP_EOL;
    }
}

$tcp_server = new Worker($config['tcp']['listen']);
$tcp_server->name = $config['tcp']['name'];
$tcp_server->count = $config['tcp']['process'];
$tcp_server->onConnect = function($conn) use ($tcp_server) {
    $msg = 'New client connect : ' . $conn->getRemoteIp();
    record($tcp_server, $msg);
};
$tcp_server->onMessage = function($conn, $data) {
    echo 'Received: ', $data, PHP_EOL;
    $conn->send('ok!!!!');
};
$tcp_server->onError = function($conn, $code, $msg) use ($tcp_server) {
    $msg = 'Error code : ' . $code . '; message : ' . $msg;
    record($tcp_server, $msg);
};
$tcp_server->onClose = function($conn) use ($tcp_server) {
    $msg = $conn->getRemoteIp() . ' close!';
    record($tcp_server, $msg);
};

Worker::runAll();