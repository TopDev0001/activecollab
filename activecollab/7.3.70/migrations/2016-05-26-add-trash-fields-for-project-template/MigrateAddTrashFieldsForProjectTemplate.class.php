<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddTrashFieldsForProjectTemplate extends AngieModelMigration
{
    public function up()
    {
        $project_templates = $this->useTableForAlter('project_templates');

        if (!$project_templates->getColumn('is_trashed')) {
            $project_templates->addColumn(DBIntegerColumn::create('is_trashed', 1, 0)->setUnsigned(true));
        }

        if (!$project_templates->getColumn('trashed_on')) {
            $project_templates->addColumn(new DBDateTimeColumn('trashed_on'));
        }

        if (!$project_templates->getColumn('trashed_by_id')) {
            $project_templates->addColumn(DBIntegerColumn::create('trashed_by_id', 10, 0)->setUnsigned(true));
        }
    }
}
