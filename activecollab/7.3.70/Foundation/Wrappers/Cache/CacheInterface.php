<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Wrappers\Cache;

use Closure;

interface CacheInterface
{
    const FILESYSTEM_BACKEND = 'filesystem';
    const MEMCACHED_BACKEND = 'memcached';
    const APC_BACKEND = 'apc';
    const REDIS_BACKEND = 'redis';

    const DEFAULT_LIFETIME = 3600;

    public function isCached($key): bool;

    /**
     * Return value for a given key.
     *
     * @param  string|array $key
     * @param  mixed        $default
     * @return mixed|null
     */
    public function get(
        $key,
        $default = null,
        bool $force_refresh = false,
        int $lifetime = null
    );

    /**
     * Return by object.
     *
     * @param  object|array  $object
     * @param  string        $sub_namespace
     * @param  Closure|mixed $default
     * @return mixed
     */
    public function getByObject(
        $object,
        $sub_namespace = null,
        $default = null,
        bool $force_refresh = false,
        int $lifetime = null
    );

    /**
     * Return true if $object is instance that we can work with.
     *
     * @param object|array $object
     */
    public function isValidObject($object): bool;

    /**
     * Cache given value.
     *
     * @param  mixed $key
     * @param  mixed $value
     * @param  mixed $lifetime
     * @return mixed
     */
    public function set($key, $value, $lifetime = null);

    /**
     * Set value by given object.
     *
     * @param  object|array $object
     * @param  mixed        $sub_namespace
     * @param  mixed        $value
     * @param  int          $lifetime
     * @return mixed
     */
    public function setByObject($object, $sub_namespace, $value, $lifetime = null);

    /**
     * Remove value and all sub-nodes.
     *
     * @param $key
     */
    public function remove($key);

    /**
     * Remove data by given object.
     *
     * $sub_namespace let you additionally specify which part of object's cache should be removed, instead of entire
     * object cache. Example:
     *
     * AngieApplication::cache()->removeByObject($user, 'permissions_cache');
     *
     * @param       $object
     * @param mixed $sub_namespace
     */
    public function removeByObject($object, $sub_namespace = null);

    public function removeByModel(string $model_name): void;
    public function clear(): void;
    public function clearModelCache(): void;

    /**
     * Return cache key for given object.
     *
     * This function receives either an object instance, or array where first element is model name and second is
     * object ID
     *
     * Optional $sub_namespace can be used to additionally dig into object's cache. String value and array of string
     * values are accepted
     *
     * @param object|array $object
     * @param mixed        $subnamespace
     */
    public function getCacheKeyForObject($object, $subnamespace = null): array;

    public function getBackendType(): ?string;
}
