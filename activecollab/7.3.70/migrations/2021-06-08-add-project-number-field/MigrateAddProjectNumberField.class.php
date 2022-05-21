<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddProjectNumberField extends AngieModelMigration
{
    public function up()
    {
        $projects = $this->useTableForAlter('projects');

        $projects->addColumn(
            (new DBIntegerColumn('project_number', 10, 0))->setUnsigned(true),
            'last_activity_on'
        );

        $this->execute('UPDATE `projects` SET `project_number` = `id`');
        $projects->addIndex(new DBIndex('project_number', DBIndex::UNIQUE));
    }
}
