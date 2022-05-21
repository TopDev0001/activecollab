<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Rename Heartbeat options.
 *
 * @package angie.migrations
 */
class MigrateHeartbeatConfigOptions extends AngieModelMigration
{
    /**
     * Migrate ups.
     */
    public function up()
    {
        $this->addConfigOption('heartbeat_instance_id');
        $this->renameConfigOption('heartbeat_incoming_key', 'heartbeat_incoming_token');
        $this->renameConfigOption('heartbeat_outgoing_backend_key', 'heartbeat_outgoing_backend_token');
        $this->renameConfigOption('heartbeat_outgoing_frontend_key', 'heartbeat_outgoing_frontend_token');
    }
}
