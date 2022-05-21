<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateRemoveArchivedOrTrashedUsersFromTeams extends AngieModelMigration
{
    public function up()
    {
        $this->execute('DELETE tu FROM team_users tu LEFT JOIN users u ON  tu.user_id = u.id WHERE u.trashed_on IS NOT NULL OR u.archived_on IS NOT NULL');
    }
}
