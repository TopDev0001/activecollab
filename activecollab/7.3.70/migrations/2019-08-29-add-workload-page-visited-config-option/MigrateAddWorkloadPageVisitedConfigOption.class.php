<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddWorkloadPageVisitedConfigOption extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('workload_page_visited', false);
    }
}
