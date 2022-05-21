<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateIsTrackingEnabledForProject extends AngieModelMigration
{
    public function up()
    {
        $this->useTableForAlter('projects')->addColumn(
            new DBBoolColumn('is_tracking_enabled', true),
            'mail_to_project_code'
        );
        $this->doneUsingTables();
    }
}
