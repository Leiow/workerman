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

function record(\Workerman\Worker $worker, $msg)
{
    if (Worker::$daemonize) {
        Libs\Record::log($worker, $msg);
    } else {
        echo $msg, PHP_EOL;
    }
}

foreach ($config['worker'] as $name => $info) {
    if ($info['process'] >= 1) {
        require realpath(__DIR__) . "/worker/{$name}.php";
    }
}

Worker::runAll();