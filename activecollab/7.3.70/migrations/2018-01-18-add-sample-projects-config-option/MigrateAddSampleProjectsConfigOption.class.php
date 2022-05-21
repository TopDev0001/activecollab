<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddSampleProjectsConfigOption extends AngieModelMigration
{
    public function up()
    {
        $config_name = 'show_sample_projects_wizard_step';

        if ($this->getConfigOptionValue($config_name) === null) {
            $this->addConfigOption($config_name, false);
        }
    }
}
