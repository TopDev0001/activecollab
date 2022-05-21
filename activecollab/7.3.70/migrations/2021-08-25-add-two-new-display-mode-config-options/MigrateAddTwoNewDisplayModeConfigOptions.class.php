<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddTwoNewDisplayModeConfigOptions extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('display_mode_completed_projects', 'grid');
        $this->addConfigOption('display_mode_project_templates', 'grid');
    }
}
