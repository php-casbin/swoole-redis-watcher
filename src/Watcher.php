<?php

namespace CasbinWatcher\SwooleRedis;

use Casbin\Persist\Watcher as WatcherContract;
use Redis;
use Closure;

class Watcher implements WatcherContract
{
    public ?Closure $callback = null;

    private $pubClient;

    private $subClient;

    private $channel;

    /**
     * The config of Watcher.
     *
     * @param array $config
     * [
     *     'host' => '127.0.0.1',
     *     'password' => '',
     *     'port' => 6379,
     *     'database' => 0,
     *     'channel' => '/casbin',
     * ]
     */
    public function __construct(array $config)
    {
        $this->pubClient = $this->createRedisClient($config);
        $this->subClient = $this->createRedisClient($config);
        $this->channel = $config['channel'] ?? '/casbin';

        go(function () {
            $this->subClient->subscribe([$this->channel], function ($redis, $channel, $message) {
                if ($this->callback) {
                    go($this->callback);
                }
            });
        });
    }

    /**
     * Sets the callback function that the watcher will call when the policy in DB has been changed by other instances.
     * A classic callback is loadPolicy() method of Enforcer class.
     *
     * @param Closure $func
     */
    public function setUpdateCallback(Closure $func): void
    {
        $this->callback = $func;
    }

    /**
     * Update calls the update callback of other instances to synchronize their policy.
     * It is usually called after changing the policy in DB, like savePolicy() method of Enforcer class,
     * addPolicy(), removePolicy(), etc.
     */
    public function update(): void
    {
        $this->pubClient->publish($this->channel, 'casbin rules updated');
    }

    /**
     * Close stops and releases the watcher, the callback function will not be called any more.
     */
    public function close(): void
    {
        $this->pubClient->close();
        $this->subClient->close();
    }

    /**
     * Create redis client
     *
     * @param array $config
     * @return Redis
     */
    private function createRedisClient(array $config): Redis
    {
        $config['host'] = $config['host'] ?? '127.0.0.1';
        $config['port'] = $config['port'] ?? 6379;
        $config['password'] = $config['password'] ?? '';
        $config['database'] = $config['database'] ?? 0;

        $client = new Redis();
        $client->pconnect($config['host'], $config['port']);
        if (!empty($config['password'])) {
            $client->auth($config['password']);
        }

        if (isset($config['database'])) {
            $client->select((int) $config['database']);
        }

        $client->setOption(Redis::OPT_READ_TIMEOUT, -1);

        return $client;
    }
}
