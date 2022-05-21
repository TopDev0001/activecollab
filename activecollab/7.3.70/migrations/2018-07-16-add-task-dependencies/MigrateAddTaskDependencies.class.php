<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddTaskDependencies extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('task_dependencies')) {
            $this->createTable(
                DB::createTable('task_dependencies')->addColumns(
                    [
                        DBFkColumn::create('parent_id', 0, true),
                        DBFkColumn::create('child_id', 0, true),
                    ]
                )->addIndices(
                    [
                        new DBIndexPrimary(['parent_id', 'child_id']),
                        DBIndex::create('child_id'),
                    ]
                )
            );
        }
    }
}
