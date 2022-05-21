<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateTaskStartOn extends AngieModelMigration
{
    public function up()
    {
        $tasks = $this->useTableForAlter('tasks');

        $tasks->addColumn(
            new DBDateColumn('start_on'),
            'updated_by_email'
        );
        $this->execute('UPDATE tasks SET start_on = due_on WHERE due_on IS NOT NULL');
        $tasks->addIndex(DBIndex::create('start_on'));

        $this->doneUsingTables();
    }
}
