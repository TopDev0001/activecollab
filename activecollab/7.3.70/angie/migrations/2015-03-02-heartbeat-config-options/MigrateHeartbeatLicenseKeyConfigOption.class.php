<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Rename instance_id option to license_key.
 *
 * @package angie.migrations
 */
class MigrateHeartbeatLicenseKeyConfigOption extends AngieModelMigration
{
    /**
     * Make sure that this migration is executed after MigrateHeartbeatConfigOptions.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateHeartbeatConfigOptions');
    }

    /**
     * Migrate ups.
     */
    public function up()
    {
        $this->renameConfigOption('heartbeat_instance_id', 'heartbeat_license_key');
    }
}
