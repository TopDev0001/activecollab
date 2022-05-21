<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddDefaultHideFromClientsConfigOption extends AngieModelMigration
{
    public function up()
    {
        if (!ConfigOptions::exists('default_hide_from_clients')) {
            $this->addConfigOption('default_hide_from_clients', false);
        }
    }
}
