<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddSortModeCompletedProjectsConfigOption extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('sort_mode_completed_projects', 'completed');
    }
}
