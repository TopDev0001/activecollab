<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\DefaultCurrencyResolver;

use ActiveCollab\Foundation\Wrappers\Cache\CacheInterface;
use ActiveCollab\Foundation\Wrappers\DataObjectPool\DataObjectPoolInterface;
use Currency;
use DBConnection;

class DefaultCurrencyResolver implements DefaultCurrencyResolverInterface
{
    private DBConnection $connection;
    private DataObjectPoolInterface $pool;
    private CacheInterface $cache;

    public function __construct(
        DBConnection $connection,
        DataObjectPoolInterface $pool,
        CacheInterface $cache
    )
    {
        $this->connection = $connection;
        $this->pool = $pool;
        $this->cache = $cache;
    }

    public function getDefaultCurrency(): Currency
    {
        $currency = $this->pool->get(Currency::class, $this->getDefaultCurrencyId());

        if (!$currency instanceof Currency) {
            throw new \RuntimeException('Failed to resolve default currency.');
        }

        return $currency;
    }

    public function getDefaultCurrencyId(): int
    {
        return (int) $this->cache->get(
            [
                'models',
                'currencies',
                'default_currency_id',
            ],
            function () {
                return $this->connection->executeFirstCell(
                    'SELECT id FROM currencies ORDER BY is_default DESC LIMIT 0, 1'
                );
            }
        );
    }
}
