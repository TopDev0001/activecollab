<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove third party fields from tasks table.
 *
 * @package ActiveCollab.migrationss
 */
class MigrateThirdPartyFieldsFromTasks extends AngieModelMigration
{
    /**
     * Migrate ups.
     */
    public function up()
    {
        $tasks = $this->useTableForAlter('tasks');

        foreach (['estimate', 'start_on', 'start_on_text', 'due_on_text', 'workflow_status'] as $column) {
            if ($tasks->getColumn($column)) {
                $tasks->dropColumn($column);
            }
        }

        $this->doneUsingTables();
    }
}
