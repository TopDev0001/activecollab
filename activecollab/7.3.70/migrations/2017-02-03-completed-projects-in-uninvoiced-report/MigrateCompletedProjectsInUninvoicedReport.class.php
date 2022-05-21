<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateCompletedProjectsInUninvoicedReport extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('completed_projects_in_uninvoiced_report', false);
    }
}
