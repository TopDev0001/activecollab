<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove subtask subscriptions.
 *
 * @package ActiveCollab.migrations
 */
class MigrateSubtaskSubscriptions extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->execute('DELETE FROM ' . $this->useTables('subscriptions')[0] . " WHERE parent_type = 'Subtask'");
        $this->doneUsingTables();
    }
}
