<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * UserWorkspaces class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class UserWorkspaces extends BaseUserWorkspaces
{
    /**
     * @var array
     */
    private static $user_workspace_count_map = [];

    /**
     * @param int[] $user_ids
     */
    public static function preloadUserWorkspaceCountMap($user_ids)
    {
        self::$user_workspace_count_map = !empty($user_ids)
            ? array_fill_keys($user_ids, 1)
            : [];

        if ($rows = DB::execute(
            'SELECT COUNT(id) AS "workspace_count", user_id FROM user_workspaces WHERE user_id IN (?) GROUP BY user_id',
            $user_ids
        )) {
            foreach ($rows as $row) {
                self::$user_workspace_count_map[(int) $row['user_id']] = (int) $row['workspace_count'];
            }
        }
    }

    /**
     * Get workspace count for user.
     *
     * @return int
     */
    public static function getWorkspaceCountForUser(User $user)
    {
        if (array_key_exists($user->getId(), self::$user_workspace_count_map)) {
            return self::$user_workspace_count_map[$user->getId()];
        } else {
            return AngieApplication::cache()->getByObject($user, 'workspace_count', function () use ($user) {
                return (int) DB::executeFirstCell('SELECT COUNT(id) FROM user_workspaces WHERE user_id = ?', $user->getId());
            });
        }
    }

    /**
     * Reset manager state (between tests for example).
     */
    public static function resetState()
    {
        self::$user_workspace_count_map = [];
    }
}
