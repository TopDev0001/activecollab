<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddProjectTemplateFeature extends AngieModelMigration
{
    public function up()
    {
        if (!ConfigOptions::exists('project_templates_enabled')) {
            $this->addConfigOption('project_templates_enabled', true);
        }
        if (!ConfigOptions::exists('project_templates_enabled_lock')) {
            $this->addConfigOption('project_templates_enabled_lock', true);
        }
    }
}
