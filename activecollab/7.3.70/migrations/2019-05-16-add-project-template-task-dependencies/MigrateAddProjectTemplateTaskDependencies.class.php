<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddProjectTemplateTaskDependencies extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('project_template_task_dependencies')) {
            $this->createTable(
                DB::createTable('project_template_task_dependencies')->addColumns(
                    [
                        new DBIdColumn(),
                        DBFkColumn::create('parent_id', 0, true),
                        DBFkColumn::create('child_id', 0, true),
                        new DBDateTimeColumn('created_on'),
                    ]
                )->addIndices(
                    [
                        DBindex::create('id', DBIndex::UNIQUE, 'id'),
                        new DBIndexPrimary(['parent_id', 'child_id']),
                        DBIndex::create('child_id'),
                    ]
                )
            );
        }
    }
}
