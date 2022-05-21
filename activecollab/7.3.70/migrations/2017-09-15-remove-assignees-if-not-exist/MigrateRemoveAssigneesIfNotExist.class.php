<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateRemoveAssigneesIfNotExist extends AngieModelMigration
{
    public function up()
    {
        $this->execute('UPDATE tasks SET assignee_id = 0 WHERE assignee_id NOT IN (SELECT id FROM users)');
        $this->execute(
            'UPDATE recurring_tasks SET assignee_id = 0 WHERE assignee_id NOT IN (SELECT id FROM users)'
        );
    }
}
