<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add my work project options.
 *
 * @package ActiveCollab.migrations
 */
class MigrateMyWorkProjectsOrder extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('my_work_projects_order');
    }
}
