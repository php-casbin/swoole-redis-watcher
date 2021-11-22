# Redis watcher for PHP-Casbin in Swoole

[![Latest Stable Version](https://poser.pugx.org/casbin/swoole-redis-watcher/v/stable)](https://packagist.org/packages/casbin/swoole-redis-watcher)
[![Total Downloads](https://poser.pugx.org/casbin/swoole-redis-watcher/downloads)](https://packagist.org/packages/casbin/swoole-redis-watcher)
[![License](https://poser.pugx.org/casbin/swoole-redis-watcher/license)](https://packagist.org/packages/casbin/swoole-redis-watcher)

Redis watcher for [PHP-Casbin](https://github.com/php-casbin/php-casbin) in [Swoole](https://www.swoole.com/) , [Casbin](https://casbin.org/) is a powerful and efficient open-source access control library.

### Installation

Via [Composer](https://getcomposer.org/).

```
composer require casbin/swoole-redis-watcher
```

### Usage

```php

require dirname(__FILE__) . '/../vendor/autoload.php';

use Casbin\Enforcer;
use CasbinWatcher\SwooleRedis\Watcher;

Co::set(['hook_flags'=> SWOOLE_HOOK_ALL]); 

$http = new Swoole\Http\Server('0.0.0.0', 9501);

$http->on('WorkerStart', function ($server, $worker_id) {
    global $enforcer;

    // Initialize the Watcher.
    $watcher = new Watcher([
        'host' => '127.0.0.1',
        'password' => '',
        'port' => 6379,
        'database' => 0,
    ]);

    // Initialize the Enforcer.
    $enforcer = new Enforcer("path/to/model.conf", "path/to/policy.csv");

    // Set the watcher for the enforcer.
    $enforcer->setWatcher($watcher);

    // By default, the watcher's callback is automatically set to the
    // $enforcer->loadPolicy() in the setWatcher() call.
    // We can change it by explicitly setting a callback.
    $watcher->setUpdateCallback(function () use ($enforcer) {
        // Now should reload all policies.
        // $enforcer->loadPolicy();
    });

};

$http->on('Request', function ($request, $response) {
    // ...
});

$http->start();

```

### Getting Help

- [php-casbin](https://github.com/php-casbin/php-casbin)

### License

This project is licensed under the [Apache 2.0 license](LICENSE).
