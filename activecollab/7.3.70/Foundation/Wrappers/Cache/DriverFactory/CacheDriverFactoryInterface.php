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
use Stash\Driver\Memcache;

interface CacheDriverFactoryInterface
{
    public function createMemcacheDriver(string $memcached_servers): Memcache;
    public function createRedisDriver(string $host, int $port = null): RedisDriver;
    public function createApcDriver(int $default_ttl): Apc;
    public function createFileSystemDriver(string $path): FileSystem;
}
