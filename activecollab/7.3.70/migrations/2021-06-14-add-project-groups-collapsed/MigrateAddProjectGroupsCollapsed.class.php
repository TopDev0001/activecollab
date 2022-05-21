<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddProjectGroupsCollapsed extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('project_groups_collapsed');
    }
}
