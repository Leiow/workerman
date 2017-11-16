<?php
namespace Libs;

class Record
{

    public static function log(\Workerman\Worker $worker, $msg)
    {
        $log_name = str_replace(' ', '', $worker->name);
        $log_path = realpath(dirname(__DIR__) . "/logs/{$log_name}.log");
        $content = '[Time]' . date('Y-m-d H:i:s', time()) . PHP_EOL .
                   '[ID]' . $worker->id . PHP_EOL . 
                   '[Name]' . $worker->name . PHP_EOL . 
                   '[Message]' . $msg . PHP_EOL .
                   str_repeat('-', 20);
        if (file_put_contents($log_path) === false) {
            return false;
        } else {
            return true;
        }
    }
}