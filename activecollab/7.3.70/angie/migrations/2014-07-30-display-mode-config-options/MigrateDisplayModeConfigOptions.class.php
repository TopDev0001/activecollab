<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add config options for page display modes.
 *
 * @package angie.migrations
 */
class MigrateDisplayModeConfigOptions extends AngieModelMigration
{
    /**
     * Upgrade the data.
     */
    public function up()
    {
        $this->addConfigOption('display_mode_projects', 'grid');
        $this->addConfigOption('display_mode_project_files', 'grid');
        $this->addConfigOption('display_mode_project_time', 'list');
        $this->addConfigOption('display_mode_invoices', 'grid');
        $this->addConfigOption('display_mode_estimates', 'grid');
        $this->addConfigOption('group_mode_people', 'first_letter');
    }
}
