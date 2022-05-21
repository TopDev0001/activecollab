<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddNewFilterConfigOptionsForNewProjectsPage extends AngieModelMigration
{
    public function up()
    {
        $new_config_options = [
            'filter_projects_client',
            'filter_projects_label',
            'filter_projects_category',
            'filter_projects_leader',
        ];

        foreach ($new_config_options as $config_option) {
            if (!ConfigOptions::exists($config_option)) {
                $this->addConfigOption($config_option, []);
            }
        }
    }
}
