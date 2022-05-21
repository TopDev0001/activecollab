<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateRemoveArchivedUsersFromProjectsAndTeamsAndSubscribers extends AngieModelMigration
{
    public function __construct()
    {
        $this->executeAfter('MigrateRemoveArchivedUsersFromAssignments');
    }

    public function up()
    {
        if ($user_ids = DB::execute('SELECT id FROM users WHERE is_archived = ?', true)) {
            DB::execute('DELETE FROM project_users WHERE user_id IN (?)', $user_ids);
            DB::execute('DELETE FROM team_users WHERE user_id IN (?)', $user_ids);
            DB::execute('DELETE FROM project_template_users WHERE user_id IN (?)', $user_ids);
            DB::execute('DELETE FROM subscriptions WHERE user_id IN (?)', $user_ids);
        }
    }
}
