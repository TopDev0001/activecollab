<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Wrappers\Cache\PoolFactory;

use ActiveCollab\Foundation\App\AccountId\AccountIdResolverInterface;
use ActiveCollab\Foundation\App\Mode\ApplicationModeInterface;
use ActiveCollab\Foundation\Wrappers\Cache\CacheInterface;
use ActiveCollab\Foundation\Wrappers\Cache\DefaultCacheLifetimeResolver\DefaultCacheLifetimeResolverInterface;
use ActiveCollab\Foundation\Wrappers\Cache\DriverFactory\CacheDriverFactoryInterface;
use Angie\FeatureFlags\FeatureFlagsInterface;
use Angie\Utils\ConstantResolverInterface;
use Angie\Utils\OnDemandStatus\OnDemandStatusInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Stash\Interfaces\DriverInterface;
use Stash\Pool;

class CachePoolFactory implements CachePoolFactoryInterface
{
    private ApplicationModeInterface $application_mode;
    private OnDemandStatusInterface $on_demand_status;
    private AccountIdResolverInterface $account_id_resolver;
    private DefaultCacheLifetimeResolverInterface $default_cache_lifetime_resolver;
    private ConstantResolverInterface $constant_resolver;
    private CacheDriverFactoryInterface $driver_factory;
    private FeatureFlagsInterface $feature_flags;
    private LoggerInterface $logger;

    public function __construct(
        ApplicationModeInterface $application_mode,
        OnDemandStatusInterface $on_demand_status,
        AccountIdResolverInterface $account_id_resolver,
        DefaultCacheLifetimeResolverInterface $default_cache_lifetime_resolver,
        ConstantResolverInterface $constant_resolver,
        CacheDriverFactoryInterface $driver_factory,
        FeatureFlagsInterface $feature_flags,
        LoggerInterface $logger
    )
    {
        $this->application_mode = $application_mode;
        $this->on_demand_status = $on_demand_status;
        $this->account_id_resolver = $account_id_resolver;
        $this->default_cache_lifetime_resolver = $default_cache_lifetime_resolver;
        $this->driver_factory = $driver_factory;
        $this->constant_resolver = $constant_resolver;
        $this->feature_flags = $feature_flags;
        $this->logger = $logger;
    }

    public function createPool(): Pool
    {
        $pool = new Pool();

        $pool->setNamespace($this->getCacheNamespace());

        $driver = $this->getDriver($this->getCacheBackend());

        $this->logger->info(
            'Cache pool initialized with "{cache_driver}" driver and "{cache_namespace}" namespace.',
            [
                'cache_driver' => get_class($driver),
                'cache_namespace' => $this->getCacheNamespace(),
            ]
        );

        $pool->setDriver($driver);

        return $pool;
    }

    private function getDriver(string $driver_type): DriverInterface
    {
        switch ($driver_type) {
            case CacheInterface::MEMCACHED_BACKEND:
                return $this->driver_factory->createMemcacheDriver(
                    (string) $this->constant_resolver->getValueForConstant('CACHE_MEMCACHED_SERVERS')
                );
            case CacheInterface::APC_BACKEND:
                return $this->driver_factory->createApcDriver(
                    $this->default_cache_lifetime_resolver->getDefaultCacheLifetime()
                );
            case CacheInterface::REDIS_BACKEND:
                return $this->driver_factory->createRedisDriver(
                    (string) $this->constant_resolver->getValueForConstant('REDIS_HOST'),
                    $this->getRedisPort(),
                );
            default:
                if (!$this->allowFileSystemCache()) {
                    throw new RuntimeException('On Demand system cannot use file system cache. Check configuration');
                }

                return $this->driver_factory->createFileSystemDriver(
                    (string) $this->constant_resolver->getValueForConstant('CACHE_PATH')
                );
        }
    }

    private function getRedisPort(): ?int
    {
        $redis_port = $this->constant_resolver->getValueForConstant('REDIS_PORT');

        return empty($redis_port) ? null : (int) $redis_port;
    }

    private function getCacheNamespace(): string
    {
        return sprintf(
            'cachestore%d',
            $this->account_id_resolver->getAccountId()
        );
    }

    private function getCacheBackend(): string
    {
        if ($this->feature_flags->isEnabled('redis_cache') || $this->feature_flags->isEnabled('redis_cache_production')) {
            return CacheInterface::REDIS_BACKEND;
        }

        switch ($this->constant_resolver->getValueForConstant('CACHE_BACKEND')) {
            case 'MemcachedCacheBackend':
            case CacheInterface::MEMCACHED_BACKEND:
                return CacheInterface::MEMCACHED_BACKEND;

            case 'APCCacheBackend':
            case CacheInterface::APC_BACKEND:
                return CacheInterface::APC_BACKEND;

            case CacheInterface::REDIS_BACKEND:
                return CacheInterface::REDIS_BACKEND;
            default:
                return CacheInterface::FILESYSTEM_BACKEND;
        }
    }

    private function allowFileSystemCache(): bool
    {
        return $this->application_mode->isInTestMode()
            || $this->application_mode->isInDevelopment()
            || !$this->on_demand_status->isOnDemand();
    }
}
