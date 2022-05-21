<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddSidebarCollapsedConfigOptions extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('sidebar_collapsed', false);
    }
}
