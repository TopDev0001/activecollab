<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Class description.
 *
 * @package
 * @subpackage
 */
class MigrateHeartbeatCleanup extends AngieModelMigration
{
    /**
     * Clean up Heartbeat configuration options.
     */
    public function up()
    {
        $this->removeConfigOption('heartbeat_proxy');
        $this->removeConfigOption('heartbeat_branding_removed');
        $this->removeConfigOption('heartbeat_license_key');

        $this->removeConfigOption('heartbeat_incoming_token');
        $this->removeConfigOption('heartbeat_outgoing_frontend_token');
        $this->removeConfigOption('heartbeat_outgoing_backend_token');

        $this->removeConfigOption('heartbeat_latest_stable_version');

        $this->removeConfigOption('heartbeat_last_pulse');
        $this->removeConfigOption('heartbeat_last_maintenance');
        $this->removeConfigOption('heartbeat_last_morning_mail');
    }
}
