<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\VisibleUserIdsResolver;

use ActiveCollab\Foundation\Wrappers\Cache\CacheInterface;
use DataObject;
use DBConnection;
use IMembers;
use User;

class VisibleUserIdsResolver implements VisibleUserIdsResolverInterface
{
    private CacheInterface $cache;
    private DBConnection $connection;

    public function __construct(
        CacheInterface $cache,
        DBConnection $connection
    )
    {
        $this->cache = $cache;
        $this->connection = $connection;
    }

    public function getAll(
        User $user,
        int $min_state = STATE_VISIBLE,
        bool $use_cache = true
    ): array
    {
        $filter_conditions = $this->getUserFilterConditions(null, $min_state, $use_cache);

        return $this->doResolve(
            $user,
            $user,
            $this->getVisibleUserIdsCacheKey(null, $min_state),
            $filter_conditions,
            $use_cache
        );
    }

    public function getInContext(
        User $user,
        IMembers $context,
        int $min_state = STATE_VISIBLE,
        bool $use_cache = true
    ): array
    {
        $user_filter_conditions = $this->getUserFilterConditions($context, $min_state, $use_cache);

        if ($context instanceof IMembers && empty($user_filter_conditions)) {
            return [];
        }

        return $this->doResolve(
            $context,
            $user,
            $this->getVisibleUserIdsCacheKey($context, $min_state),
            $user_filter_conditions,
            $use_cache
        );
    }

    private function doResolve(
        DataObject $cache_context,
        User $user,
        array $cache_key,
        array $user_filter_conditions,
        bool $use_cache
    ): array
    {
        return $this->cache->getByObject(
            $cache_context,
            $cache_key,
            function () use ($user, $user_filter_conditions, $cache_context) {
                if ($user->isOwner()) {
                    return $this->getForOwners($user_filter_conditions);
                }

                $user_filter_conditions = empty($user_filter_conditions)
                    ? ''
                    : sprintf('AND (%s)', implode(' AND ', $user_filter_conditions));

                $user_ids = [];

                // user always can see himself
                if ($user->is($cache_context)) {
                    $user_ids = [$user->getId()];
                }

                // Get company members.
                $company_user_ids = $this->getCompanyMemberIds($user, $user_filter_conditions);

                // Extend the list with people that $user worked with on projects.
                $project_member_ids = $this->getProjectMemberIds($user, $user_filter_conditions);

                // Extend the list with people that $user worked with in team.
                $team_member_ids = $this->getTeamMemberIds($user, $user_filter_conditions);

                // Extend the list with people that $user is involved with in group conversations.
                $conversation_member_ids = $this->getConversationMemberIds($user, $user_filter_conditions);

                if ($company_user_ids) {
                    $user_ids = array_merge(
                        $user_ids,
                        $company_user_ids
                    );
                }

                if ($project_member_ids) {
                    $user_ids = array_merge(
                        $user_ids,
                        $project_member_ids
                    );
                }

                if ($team_member_ids) {
                    $user_ids = array_merge(
                        $user_ids,
                        $team_member_ids
                    );
                }

                if ($conversation_member_ids) {
                    $user_ids = array_merge(
                        $user_ids,
                        $conversation_member_ids
                    );
                }

                if (!empty($user_ids)) {
                    $user_ids = array_unique($user_ids);
                    sort($user_ids);
                }

                return $user_ids;
            },
            !$use_cache
        );
    }

    private function getForOwners(array $filter_conditions): array
    {
        $user_ids = $this->connection->executeFirstColumn(
            sprintf(
                'SELECT `id` FROM `users` %s ORDER BY `id`',
                empty($filter_conditions)
                    ? ''
                    : 'WHERE ' . implode(' AND ', $filter_conditions)
            )
        );

        if (empty($user_ids)) {
            $user_ids = [];
        }

        return $user_ids;
    }

    private function getCompanyMemberIds(User $user, string $user_filter_conditions): array
    {
        if (!empty($user->getCompanyId())) {
            $user_ids = $this->connection->executeFirstColumn(
                sprintf(
                    'SELECT id FROM users WHERE company_id = ? %s ORDER BY id',
                    $user_filter_conditions,
                ),
                [
                    $user->getCompanyId(),
                ]
            );
        }

        if (empty($user_ids)) {
            $user_ids = [];
        }

        return $user_ids;
    }

    private function getProjectMemberIds(User $user, string $user_filter_conditions): ?array
    {
        $project_ids = $this->getProjectIds($user);

        if (empty($project_ids)) {
            return null;
        }

        // Get other users that this user worked with in the past
        return $this->connection->executeFirstColumn(
            sprintf(
                'SELECT users.id FROM users JOIN project_users ON users.id = project_users.user_id WHERE project_users.project_id IN (?) %s ORDER BY users.id',
                $user_filter_conditions
            ),
            [
                $project_ids,
            ]
        );
    }

    private function getTeamMemberIds(User $user, string $user_filter_conditions): ?array
    {
        $team_ids = $this->getTeamIds($user);

        if (empty($team_ids)) {
            return null;
        }

        // Get other users that this user worked with in the past in team
        return $this->connection->executeFirstColumn(
            sprintf(
                'SELECT users.id FROM users JOIN team_users ON users.id = team_users.user_id WHERE team_users.team_id IN (?) %s ORDER BY users.id',
                $user_filter_conditions
            ),
            [
                $team_ids,
            ]
        );
    }

    private function getConversationMemberIds(User $user, string $user_filter_conditions): ?array
    {
        $conversation_ids = $this->getConversationIds($user);

        if (empty($conversation_ids)) {
            return null;
        }

        // Get other users that this user worked with in the past
        return $this->connection->executeFirstColumn(
            sprintf(
                'SELECT users.id FROM users JOIN conversation_users ON users.id = conversation_users.user_id WHERE conversation_users.conversation_id IN (?) %s ORDER BY users.id',
                $user_filter_conditions
            ),
            [
                $conversation_ids,
            ]
        );
    }

    private function getProjectIds(User $user): ?array
    {
        return $this->connection->executeFirstColumn(
            'SELECT DISTINCT `project_id` FROM `project_users` WHERE `user_id` = ?',
            [
                $user->getId(),
            ]
        );
    }

    private function getTeamIds(User $user): ?array
    {
        return $this->connection->executeFirstColumn(
            'SELECT DISTINCT `team_id` FROM `team_users` WHERE `user_id` = ?',
            [
                $user->getId(),
            ]
        );
    }

    private function getConversationIds(User $user): ?array
    {
        return $this->connection->executeFirstColumn(
            'SELECT DISTINCT `conversation_id` FROM `conversation_users` WHERE `user_id` = ?',
            [
                $user->getId(),
            ]
        );
    }

    private function getUserFilterConditions(
        IMembers $context = null,
        int $min_state = STATE_VISIBLE,
        bool $use_cache = true
    ): array
    {
        $conditions = [];

        if ($context instanceof IMembers) {
            $context_member_ids = $context->getMemberIds($use_cache);

            if (empty($context_member_ids)) {
                return [];
            }

            $conditions[] = $this->connection->prepare(
                '(users.id IN (?))',
                [
                    $context_member_ids,
                ]
            );
        }

        if ($min_state === STATE_VISIBLE) {
            $conditions[] = $this->connection->prepare(
                '(users.is_archived = ? AND users.is_trashed = ?)',
                [
                    false,
                    false,
                ]
            );
        } elseif ($min_state === STATE_ARCHIVED) {
            $conditions[] = $this->connection->prepare(
                '(users.is_trashed = ?)',
                [
                    false,
                ]
            );
        }

        return $conditions;
    }

    private function getVisibleUserIdsCacheKey(
        IMembers $context = null,
        int $min_state = STATE_VISIBLE
    ): array
    {
        $cache_key = [
            'visible_users',
        ];

        if ($context instanceof IMembers && $context instanceof DataObject) {
            $cache_key[] = sprintf(
                '%s-%d', $context->getModelName(false, true),
                $context->getId()
            );
        }

        $cache_key[] = $min_state;

        return $cache_key;
    }
}
