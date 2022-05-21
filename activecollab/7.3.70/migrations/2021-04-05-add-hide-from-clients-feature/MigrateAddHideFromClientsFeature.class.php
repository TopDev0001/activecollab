<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddHideFromClientsFeature extends AngieModelMigration
{
    public function up()
    {
        if (!ConfigOptions::exists('hide_from_clients_enabled')) {
            $this->addConfigOption('hide_from_clients_enabled', true);
        }
        if (!ConfigOptions::exists('hide_from_clients_enabled_lock')) {
            $this->addConfigOption('hide_from_clients_enabled_lock', true);
        }
    }
}
