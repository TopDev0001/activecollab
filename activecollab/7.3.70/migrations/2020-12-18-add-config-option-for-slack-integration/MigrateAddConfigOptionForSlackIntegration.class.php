<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddConfigOptionForSlackIntegration extends AngieModelMigration
{
    public function up()
    {
        if (!ConfigOptions::exists('slack_integration_enabled')) {
            $this->addConfigOption('slack_integration_enabled', true);
        }

        if (!ConfigOptions::exists('slack_integration_enabled_lock')) {
            $this->addConfigOption('slack_integration_enabled_lock', true);
        }
    }
}
