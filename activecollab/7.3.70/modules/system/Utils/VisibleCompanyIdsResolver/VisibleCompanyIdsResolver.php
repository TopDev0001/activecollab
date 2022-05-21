<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\VisibleCompanyIdsResolver;

use ActiveCollab\Foundation\Wrappers\Cache\CacheInterface;
use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;
use DBConnection;
use User;

class VisibleCompanyIdsResolver implements VisibleCompanyIdsResolverInterface
{
    private OwnerCompanyResolverInterface $owner_company_resolver;
    private CacheInterface $cache;
    private DBConnection $connection;

    public function __construct(
        OwnerCompanyResolverInterface $owner_company_resolver,
        CacheInterface $cache,
        DBConnection $connection
    )
    {
        $this->owner_company_resolver = $owner_company_resolver;
        $this->cache = $cache;
        $this->connection = $connection;
    }

    public function getAll(
        User $user,
        bool $use_cache = true
    ): array
    {
        return $this->cache->getByObject(
            $user,
            [
                'visible_companies',
            ],
            function () use ($user, $use_cache) {
                $result = [
                    $this->owner_company_resolver->getId(),
                ];

                $result = $this->loadCompaniesCreatedByUser($user, $result);
                $result = $this->loadCompaniesViaVisibleUsers($user, $use_cache, $result);
                $result = $this->loadProjectCompanies($user, $result);

                sort($result);

                return $result;
            },
            empty($use_cache)
        );
    }

    private function loadCompaniesCreatedByUser(User $user, array $result): array
    {
        $companies_created_by_me = $this->connection->executeFirstColumn(
            'SELECT `id` FROM `companies` WHERE `created_by_id` = ? AND `id` NOT IN (?)',
            [
                $user->getId(),
                $result,
            ]
        );

        if (!empty($companies_created_by_me)) {
            $result = array_merge($result, $companies_created_by_me);
        }

        return $result;
    }

    private function loadCompaniesViaVisibleUsers(
        User $user,
        bool $use_cache,
        array $result
    ): array
    {
        $visible_user_ids = $user->getVisibleUserIds(STATE_TRASHED, $use_cache);

        if (!empty($visible_user_ids)) {
            $other_companies = $this->connection->executeFirstColumn(
                'SELECT DISTINCT c.id FROM companies AS c JOIN users AS u ON c.id = u.company_id WHERE u.id IN (?) AND c.id NOT IN (?) ORDER BY c.id',
                [
                    $visible_user_ids,
                    $result,
                ]
            );

            if (!empty($other_companies)) {
                $result = array_merge($result, $other_companies);
            }
        }

        return $result;
    }

    private function loadProjectCompanies(User $user, array $result): array
    {
        $project_ids = $user->getProjectIds();

        if (empty($project_ids)) {
            return $result;
        }

        $involved_company_ids = $this->connection->executeFirstColumn(
            'SELECT DISTINCT `company_id` FROM `projects` WHERE `id` IN (?) AND `company_id` NOT IN (?)',
            [
                $project_ids,
                $result,
            ]
        );

        if (!empty($involved_company_ids)) {
            $result = array_merge($result, $involved_company_ids);
        }

        return $result;
    }
}
