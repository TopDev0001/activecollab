<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddFilterCompletedProjectsConfigOptions extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('filter_completed_projects_client', []);
        $this->addConfigOption('filter_completed_projects_label', []);
        $this->addConfigOption('filter_completed_projects_category', []);
        $this->addConfigOption('filter_completed_projects_leader', []);
    }
}
