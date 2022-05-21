<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddMainMenuExpandedProjects extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('main_menu_expanded_projects', false);
    }
}
