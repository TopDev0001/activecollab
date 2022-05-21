<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Wrappers\Cache\DriverFactory;

use ActiveCollab\Foundation\Wrappers\Cache\RedisDriver;
use Stash\Driver\Apc;
use Stash\Driver\FileSystem;
use Stash\Driver\FileSystem\SerializerEncoder;
use Stash\Driver\Memcache;

class CacheDriverFactory implements CacheDriverFactoryInterface
{
    public function createMemcacheDriver(string $memcached_servers): Memcache
    {
        return new Memcache(
            [
                'servers' => $this->parseMemcachedServersList($memcached_servers),
            ]
        );
    }

    private function parseMemcachedServersList(string $list): array
    {
        $result = [];

        if ($list) {
            foreach (explode(',', $list) as $server) {
                if (strpos($server, '/') !== false) {
                    [$server_url, $weight] = explode('/', $server);
                } else {
                    $server_url = $server;
                    $weight = 1;
                }

                $parts = parse_url($server_url);

                if (empty($parts['host'])) {
                    if (empty($parts['path'])) {
                        continue; // Ignore
                    } else {
                        $host = $parts['path'];
                    }
                } else {
                    $host = $parts['host'];
                }

                $result[] = [
                    $host,
                    array_var($parts, 'port', '11211'),
                    $weight,
                ];
            }
        }

        return $result;
    }

    public function createRedisDriver(string $host, int $port = null): RedisDriver
    {
        $server = [$host];

        if ($port) {
            $server[] = $port;
        }

        return new RedisDriver(
            [
                'servers' => [$server],
            ]
        );
    }

    public function createApcDriver(int $default_ttl): Apc
    {
        return new Apc(
            [
                'ttl' => $default_ttl,
            ]
        );
    }

    public function createFileSystemDriver(string $path): FileSystem
    {
        return new FileSystem(
            [
                'dirSplit' => 1,
                'path' => $path,
                'filePermissions' => 0777,
                'dirPermissions' => 0777,
                'encoder' => new SerializerEncoder(),
            ]
        );
    }
}
