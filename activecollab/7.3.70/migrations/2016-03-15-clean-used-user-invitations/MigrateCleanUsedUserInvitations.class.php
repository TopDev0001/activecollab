<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove used user invitations which are left in db and user accessed AC.
 *
 * @package ActiveCollab.migrations
 */
class MigrateCleanUsedUserInvitations extends AngieModelMigration
{
    public function up()
    {
        if ($invitations = DB::executeFirstColumn('SELECT DISTINCT accessed_by_id FROM access_logs')) {
            DB::execute('DELETE FROM user_invitations WHERE user_id IN (?)', $invitations);
        }
    }
}
