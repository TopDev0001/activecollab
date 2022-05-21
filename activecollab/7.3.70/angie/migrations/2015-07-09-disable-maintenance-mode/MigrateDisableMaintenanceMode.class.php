<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Disable maintenance mode during upgrade (if enabled).
 *
 * @package angie.migrations
 */
class MigrateDisableMaintenanceMode extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->setConfigOptionValue('maintenance_enabled', false);
        $this->setConfigOptionValue('maintenance_message', '');
    }
}
