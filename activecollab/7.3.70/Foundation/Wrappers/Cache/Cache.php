<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Wrappers\Cache;

use ActiveCollab\Foundation\Wrappers\Cache\DefaultCacheLifetimeResolver\DefaultCacheLifetimeResolverInterface;
use ActiveCollab\Foundation\Wrappers\Cache\PoolFactory\CachePoolFactoryInterface;
use Angie\Inflector;
use Closure;
use DataObject;
use InvalidParamError;
use Stash\Driver\Apc;
use Stash\Driver\FileSystem;
use Stash\Driver\Memcache;
use Stash\Driver\Redis;
use Stash\Interfaces\ItemInterface;
use Stash\Pool;

class Cache implements CacheInterface
{
    private CachePoolFactoryInterface $pool_factory;
    private DefaultCacheLifetimeResolverInterface $default_cache_lifetime_resolver;

    public function __construct(
        CachePoolFactoryInterface $pool_factory,
        DefaultCacheLifetimeResolverInterface $default_cache_lifetime_resolver
    )
    {
        $this->pool_factory = $pool_factory;
        $this->default_cache_lifetime_resolver = $default_cache_lifetime_resolver;
    }

    /**
     * Return true if $key is cached.
     *
     * @param mixed $key
     */
    public function isCached($key): bool
    {
        $stash = $this->getStash($this->getKey($key));
        $stash->get();

        return !$stash->isMiss();
    }

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
    )
    {
        $stash = $this->getStash($this->getKey($key));

        $data = $stash->get();

        if ($force_refresh || $stash->isMiss()) {
            $data = is_callable($default)
                ? call_user_func($default)
                : $default;

            $stash
                ->set($data)
                ->setTTL($this->getLifetime($lifetime));

            $this->pool->save($stash);
        }

        return $data;
    }

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
    )
    {
        if (!$this->isValidObject($object)) {
            throw new InvalidParamError('object', $object, '$object is not a valid cache context');
        }

        return $this->get($this->getCacheKeyForObject($object, $sub_namespace), $default, $force_refresh, $lifetime);
    }

    /**
     * Return true if $object is instance that we can work with.
     *
     * @param object|array $object
     */
    public function isValidObject($object): bool
    {
        if ($object instanceof DataObject) {
            return $object->isLoaded();
        } elseif (is_array($object) && count($object) == 2) {
            return true;
        } else {
            return is_object($object)
                && method_exists($object, 'getId')
                && method_exists($object, 'getModelName')
                && $object->getId();
        }
    }

    /**
     * Cache given value.
     *
     * @param  mixed $key
     * @param  mixed $value
     * @param  mixed $lifetime
     * @return mixed
     */
    public function set($key, $value, $lifetime = null)
    {
        $stash = $this
            ->getStash($this->getKey($key))
                ->set($value)
                ->setTTL($this->getLifetime($lifetime));

        $this->pool->save($stash);

        return $value;
    }

    /**
     * Set value by given object.
     *
     * @param  object|array $object
     * @param  mixed        $sub_namespace
     * @param  mixed        $value
     * @param  int          $lifetime
     * @return mixed
     */
    public function setByObject($object, $sub_namespace, $value, $lifetime = null)
    {
        if ($this->isValidObject($object)) {
            return $this->set($this->getCacheKeyForObject($object, $sub_namespace), $value, $lifetime);
        }

        return false; // Not supported for objects that are not persisted
    }

    /**
     * Remove value and all sub-nodes.
     *
     * @param $key
     */
    public function remove($key)
    {
        $this->getStash($key)->clear();
    }

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
    public function removeByObject($object, $sub_namespace = null)
    {
        $this->remove($this->getCacheKeyForObject($object, $sub_namespace));
    }

    public function removeByModel(string $model_name): void
    {
        $this->remove(
            [
                'models',
                $model_name,
            ]
        );
    }

    public function clear(): void
    {
        if ($this->getPool()->getDriver() instanceof FileSystem) {
            empty_dir(CACHE_PATH, true);
        } else {
            $this->getPool()->clear();
        }
    }

    public function clearModelCache(): void
    {
        $this->remove('models');
    }

    public function getCacheKeyForObject($object, $subnamespace = null): array
    {
        if (!$this->isValidObject($object)) {
            throw new InvalidParamError('object', $object, '$object is expected to be loaded object instance with getId method defined or an array that has model name and object ID');
        }

        if ($object instanceof DataObject) {
            return get_data_object_cache_key(
                $object->getModelName(true),
                $object->getId(),
                $subnamespace
            );
        } elseif (is_array($object) && count($object) == 2) {
            return get_data_object_cache_key($object[0], $object[1], $subnamespace);
        } else {
            return get_data_object_cache_key(
                Inflector::pluralize(Inflector::underscore(get_class($object))),
                $object->getId(),
                $subnamespace
            );
        }
    }

    // ---------------------------------------------------
    //  Internal, Stash Related Functions
    // ---------------------------------------------------

    private ?Pool $pool = null;

    private function getPool(): Pool
    {
        if (empty($this->pool)) {
            $this->pool = $this->pool_factory->createPool();
        }

        return $this->pool;
    }

    private function getStash($key): ItemInterface
    {
        return $this->getPool()->getItem(
            is_array($key) ? implode('/', $key) : $key
        );
    }

    private function getKey($key): string
    {
        return is_array($key)
            ? implode('/', $key)
            : (string) $key;
    }

    private function getLifetime(int $lifetime = null): int
    {
        return $lifetime ?? $this->default_cache_lifetime_resolver->getDefaultCacheLifetime();
    }

    public function getBackendType(): ?string
    {
        $driver = $this->getPool()->getDriver();

        if ($driver instanceof Memcache) {
            return self::MEMCACHED_BACKEND;
        } elseif ($driver instanceof Apc) {
            return self::APC_BACKEND;
        } elseif ($driver instanceof Redis) {
            return self::REDIS_BACKEND;
        } elseif ($driver instanceof FileSystem) {
            return self::FILESYSTEM_BACKEND;
        }

        return null;
    }
}
