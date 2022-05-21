<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Disable firewall during upgrade (if enabled).
 *
 * @package angie.migrations
 */
class MigrateDisableFirewall extends AngieModelMigration
{
    /**
     * Execute the migration.
     */
    public function up()
    {
        $this->setConfigOptionValue('firewall_enabled', false);
    }
}
