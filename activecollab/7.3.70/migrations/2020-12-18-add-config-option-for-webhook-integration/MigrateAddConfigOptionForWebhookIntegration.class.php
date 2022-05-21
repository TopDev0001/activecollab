<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddConfigOptionForWebhookIntegration extends AngieModelMigration
{
    public function up()
    {
        if (!ConfigOptions::exists('webhooks_integration_enabled')) {
            $this->addConfigOption('webhooks_integration_enabled', true);
        }

        if (!ConfigOptions::exists('webhooks_integration_enabled_lock')) {
            $this->addConfigOption('webhooks_integration_enabled_lock', true);
        }
    }
}
