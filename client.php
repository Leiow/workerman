<?php
require 'vendor/autoload.php';

use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Timer;

$worker_path = realpath(__DIR__);
if (file_exists($worker_path . '/config/config.json')) {
    $config = json_decode(file_get_contents($worker_path . '/config/config.json'), true);
} else {
    exit('Can not find the configuration file!');
}

require realpath(__DIR__) . '/worker/tcp.php';
require realpath(__DIR__) . '/worker/tcp_sec.php';

Worker::runAll();