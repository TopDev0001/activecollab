<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddFilterClientProjectsOption extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('filter_client_projects', 'any');
        $this->addConfigOption('filter_label_projects', 'any');
        $this->addConfigOption('filter_category_projects', 'any');
    }
}
