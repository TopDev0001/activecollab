<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop updated_by_* columns from subtasks table.
 *
 * @package ActiveCollab.migrations
 */
class MigrateSubtaskUpdatedOnByCleanup extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $subtasks = $this->useTableForAlter('subtasks');

        $subtasks->dropColumn('updated_by_id');
        $subtasks->dropColumn('updated_by_name');
        $subtasks->dropColumn('updated_by_email');

        $this->doneUsingTables();
    }
}
