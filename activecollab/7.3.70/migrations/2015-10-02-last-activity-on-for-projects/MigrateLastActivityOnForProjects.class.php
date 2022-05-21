<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateLastActivityOnForProjects extends AngieModelMigration
{
    public function up()
    {
        $project = $this->useTableForAlter('projects');

        if ($project->getColumn('last_activity_on') === null) {
            $project->addColumn(
                new DBDateTimeColumn('last_activity_on'),
                'updated_on'
            );
        }

        $this->execute('UPDATE projects SET last_activity_on = updated_on');
        $this->doneUsingTables();
    }
}
