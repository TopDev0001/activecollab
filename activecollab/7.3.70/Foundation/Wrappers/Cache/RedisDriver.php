<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Foundation\Wrappers\Cache;

use Redis;
use RedisArray;
use RedisException;
use Stash\Driver\AbstractDriver;
use Stash\Utilities;

/**
 * The Redis driver is used for storing data on a Redis system. This class uses
 * the PhpRedis extension to access the Redis server.
 *
 * @package Stash
 * @author  Robert Hafner <tedivm@tedivm.com>
 */
class RedisDriver extends AbstractDriver
{
    /**
     * The Redis drivers.
     *
     * @var Redis|RedisArray
     */
    protected $redis;
    protected array $keyCache = [];
    protected array $redisArrayOptionNames = [
        'previous',
        'function',
        'distributor',
        'index',
        'autorehash',
        'pconnect',
        'retry_interval',
        'lazy_connect',
        'connect_timeout',
    ];

    /**
     * The options array should contain an array of servers,.
     *
     * The "server" option expects an array of servers, with each server being represented by an associative array. Each
     * redis config must have either a "socket" or a "server" value, and optional "port" and "ttl" values (with the ttl
     * representing server timeout, not cache expiration).
     *
     * The "database" option lets developers specific which specific database to use.
     *
     * The "password" option is used for clusters which required authentication.
     */
    protected function setOptions(array $options = [])
    {
        $options += $this->getDefaultOptions();

        // Normalize Server Options
        if (isset($options['servers'])) {
            $unprocessedServers = (is_array($options['servers']))
                ? $options['servers']
                : [$options['servers']];
            unset($options['servers']);

            $servers = [];
            foreach ($unprocessedServers as $server) {
                $ttl = '.1';
                if (isset($server['ttl'])) {
                    $ttl = $server['ttl'];
                } elseif (isset($server[2])) {
                    $ttl = $server[2];
                }

                if (isset($server['socket'])) {
                    $servers[] = ['socket' => $server['socket'], 'ttl' => $ttl];
                } else {
                    $host = '127.0.0.1';
                    if (isset($server['server'])) {
                        $host = $server['server'];
                    } elseif (isset($server[0])) {
                        $host = $server[0];
                    }

                    $port = '6379';
                    if (isset($server['port'])) {
                        $port = $server['port'];
                    } elseif (isset($server[1])) {
                        $port = $server[1];
                    }

                    $servers[] = ['server' => $host, 'port' => $port, 'ttl' => $ttl];
                }
            }
        } else {
            $servers = [['server' => '127.0.0.1', 'port' => '6379', 'ttl' => 0.1]];
        }

        // this will have to be revisited to support multiple servers, using
        // the RedisArray object. That object acts as a proxy object, meaning
        // most of the class will be the same even after the changes.

        if (count($servers) == 1) {
            $server = $servers[0];
            $redis = new Redis();

            if (isset($server['socket']) && $server['socket']) {
                $redis->connect($server['socket']);
            } else {
                $port = $server['port'] ?? 6379;
                $ttl = $server['ttl'] ?? 0.1;
                $redis->connect($server['server'], $port, $ttl);
            }

            // auth - just password
            if (isset($options['password'])) {
                $redis->auth($options['password']);
            }

            $this->redis = $redis;
        } else {
            $redisArrayOptions = [];
            foreach ($this->redisArrayOptionNames as $optionName) {
                if (array_key_exists($optionName, $options)) {
                    $redisArrayOptions[$optionName] = $options[$optionName];
                }
            }

            $serverArray = [];
            foreach ($servers as $server) {
                $serverString = $server['server'];
                if (isset($server['port'])) {
                    $serverString .= ':' . $server['port'];
                }

                $serverArray[] = $serverString;
            }

            $redis = new RedisArray($serverArray, $redisArrayOptions);
        }

        // select database
        if (isset($options['database'])) {
            $redis->select($options['database']);
        }

        $this->redis = $redis;
    }

    /**
     * Properly close the connection.
     */
    public function __destruct()
    {
        if ($this->redis instanceof Redis) {
            try {
                $this->redis->close();
            } catch (RedisException $e) {
                /*
                 * \Redis::close will throw a \RedisException("Redis server went away") exception if
                 * we haven't previously been able to connect to Redis or the connection has severed.
                 */
            }
        }
    }

    public function getData($key)
    {
        return unserialize($this->redis->get($this->makeKeyString($key)));
    }

    public function storeData($key, $data, $expiration)
    {
        $store = serialize(
            [
                'data' => $data,
                'expiration' => $expiration,
            ]
        );

        if (is_null($expiration)) {
            return $this->redis->set($this->makeKeyString($key), $store);
        }

        $ttl = $expiration - time();

        // Prevent us from even passing a negative ttl'd item to redis,
        // since it will just round up to zero and cache forever.
        if ($ttl < 1) {
            return true;
        }

        return $this->redis->setex($this->makeKeyString($key), $ttl, $store);
    }

    public function clear($key = null)
    {
        if (is_null($key)) {
            $this->redis->flushDB();

            return true;
        }

        $keyString = $this->makeKeyString($key, true);
        $keyReal = $this->makeKeyString($key);
        $this->redis->incr($keyString); // increment index for children items
        $this->redis->del($keyReal); // remove direct item.
        $this->keyCache = [];

        return true;
    }

    public function purge()
    {
        return true;
    }

    public static function isAvailable()
    {
        return class_exists('Redis', false);
    }

    /**
     * Turns a key array into a key string. This includes running the indexing functions used to manage the Redis
     * hierarchical storage.
     *
     * When requested the actual path, rather than a normalized value, is returned.
     *
     * @param  array  $key
     * @param  bool   $path
     * @return string
     */
    protected function makeKeyString($key, $path = false)
    {
        $key = Utilities::normalizeKeys($key);

        $keyString = 'cache:::';
        $pathKey = ':pathdb::';
        foreach ($key as $name) {
            //a. cache:::name
            //b. cache:::name0:::sub
            $keyString .= $name;

            //a. :pathdb::cache:::name
            //b. :pathdb::cache:::name0:::sub
            $pathKey = ':pathdb::' . $keyString;
            $pathKey = md5($pathKey);

            if (isset($this->keyCache[$pathKey])) {
                $index = $this->keyCache[$pathKey];
            } else {
                $index = $this->redis->get($pathKey);
                $this->keyCache[$pathKey] = $index;
            }

            //a. cache:::name0:::
            //b. cache:::name0:::sub1:::
            $keyString .= '_' . $index . ':::';
        }

        return $path ? $pathKey : md5($keyString);
    }

    public function isPersistent()
    {
        return true;
    }
}
