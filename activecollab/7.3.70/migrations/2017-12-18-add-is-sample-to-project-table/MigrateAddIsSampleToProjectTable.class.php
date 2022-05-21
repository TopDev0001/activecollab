<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddIsSampleToProjectTable extends AngieModelMigration
{
    public function up()
    {
        $projects = $this->useTableForAlter('projects');
        $column_name = 'is_sample';

        if (!$projects->getColumn($column_name)) {
            $projects->addColumn(
                new DBBoolColumn($column_name),
                'trashed_by_id'
            );
        }

        $this->doneUsingTables();
    }
}
