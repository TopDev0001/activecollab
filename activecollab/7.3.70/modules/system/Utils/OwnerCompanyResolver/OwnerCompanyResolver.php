<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\OwnerCompanyResolver;

use ActiveCollab\Foundation\Wrappers\Cache\CacheInterface;
use ActiveCollab\Foundation\Wrappers\DataObjectPool\DataObjectPoolInterface;
use Company;
use DBConnection;
use RuntimeException;

class OwnerCompanyResolver implements OwnerCompanyResolverInterface
{
    private CacheInterface $cache;
    private DBConnection $connection;
    private DataObjectPoolInterface $data_object_pool;

    public function __construct(
        CacheInterface $cache,
        DBConnection $connection,
        DataObjectPoolInterface $data_object_pool
    )
    {
        $this->cache = $cache;
        $this->connection = $connection;
        $this->data_object_pool = $data_object_pool;
    }

    private ?int $owner_company_id = null;

    public function getId(): int
    {
        if ($this->owner_company_id === null) {
            $this->owner_company_id = $this->cache->get(
                [
                    'models',
                    'companies',
                    'owner_company_id',
                ],
                function () {
                    return (int) $this->connection->executeFirstCell(
                        'SELECT id FROM companies WHERE is_owner = ? LIMIT 0, 1',
                        [
                            true,
                        ]
                    );
                }
            );

            if (empty($this->owner_company_id)) {
                throw new RuntimeException('Failed to resolve owner company ID.');
            }
        }

        return $this->owner_company_id;
    }

    private ?Company $company = null;

    public function getCompany(): Company
    {
        if ($this->company === null) {
            $this->company = $this->data_object_pool->get(Company::class, $this->getId());

            if (empty($this->company)) {
                throw new RuntimeException('Failed to find owner company.');
            }
        }

        return $this->company;
    }
}
