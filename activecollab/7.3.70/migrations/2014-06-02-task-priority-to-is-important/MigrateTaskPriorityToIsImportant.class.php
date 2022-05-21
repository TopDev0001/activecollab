<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateTaskPriorityToIsImportant extends AngieModelMigration
{
    public function up()
    {
        $tasks = $this->useTableForAlter('tasks');

        $this->execute('UPDATE ' . $tasks->getName() . ' SET priority = ? WHERE priority IS NULL OR priority <= ?', false, 0);
        $this->execute('UPDATE ' . $tasks->getName() . ' SET priority = ? WHERE priority > ?', true, 0);

        $tasks->alterColumn(
            'priority',
            new DBBoolColumn('is_important')
        );

        $this->doneUsingTables();
    }
}
