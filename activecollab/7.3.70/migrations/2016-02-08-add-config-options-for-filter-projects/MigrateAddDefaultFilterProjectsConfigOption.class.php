<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add default filter projects config option.
 *
 * @package ActiveCollab.migrations
 */
class MigrateAddDefaultFilterProjectsConfigOption extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('filter_client_projects', 'any');
        $this->addConfigOption('filter_label_projects', 'any');
        $this->addConfigOption('filter_category_projects', 'any');
    }
}
