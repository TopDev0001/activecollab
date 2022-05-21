<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop estimated by / on field (in favor of history which already trask all changes).
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateDropEstimatedOnBy extends AngieModelMigration
{
    /**
     * Remove related tasks.
     */
    public function up()
    {
        $tasks = $this->useTableForAlter('tasks');

        $tasks->dropColumn('estimated_on');
        $tasks->dropColumn('estimated_by_id');
        $tasks->dropColumn('estimated_by_name');
        $tasks->dropColumn('estimated_by_email');

        $this->doneUsingTables();
    }
}
