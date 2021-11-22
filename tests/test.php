<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

use Casbin\Enforcer;
use CasbinWatcher\SwooleRedis\Watcher;

Co::set(['hook_flags' => SWOOLE_HOOK_ALL]);

$http = new Swoole\Http\Server('0.0.0.0', 9501);

$http->on('WorkerStart', function ($server, $worker_id) {
    global $enforcer;

    $watcher = new Watcher([
        'host' => getenv('REDIS_HOST') ? getenv('REDIS_HOST') : '127.0.0.1',
        'password' => getenv('REDIS_PASSWORD') ? getenv('REDIS_PASSWORD') : '',
        'port' => getenv('REDIS_PORT') ? getenv('REDIS_PORT') : 6379,
        'database' => getenv('REDIS_DB') ? getenv('REDIS_DB') : 0,
    ]);

    $enforcer = new Enforcer(dirname(__FILE__) . '/../vendor/casbin/casbin/examples/basic_model.conf', dirname(__FILE__) . '/../vendor/casbin/casbin/examples/basic_policy.csv');

    $enforcer->setWatcher($watcher);

    $watcher->setUpdateCallback(function () {
        echo "Now should reload all policies." . PHP_EOL;
    });
});

$http->on('Request', function ($request, $response) {
    global $enforcer;

    $enforcer->savePolicy();

    $response->header('Content-Type', 'text/html; charset=utf-8');
    $response->end('<h1>Hello Swoole. #' . rand(1000, 9999) . '</h1>');
});

$http->start();
