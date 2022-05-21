<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateClientReportingForProjects extends AngieModelMigration
{
    public function up()
    {
        $this->useTableForAlter('projects')->addColumn(
            new DBBoolColumn('is_client_reporting_enabled'),
            'is_tracking_enabled'
        );
        $this->doneUsingTables();
    }
}
