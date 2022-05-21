<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate angie to Heartbeat proxy settings.
 *
 * @package angie.migrations
 */
class MigrateHeartbeatProxySettings extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $proxy_protocol = $this->getConfigOptionValue('network_proxy_protocol');
        $proxy_address = $this->getConfigOptionValue('network_proxy_address');
        $proxy_port = $this->getConfigOptionValue('network_proxy_port');

        if ($this->getConfigOptionValue('network_proxy_enabled') && $proxy_protocol && $proxy_address && $proxy_port) {
            $this->addConfigOption('heartbeat_proxy', "$proxy_protocol://$proxy_address:$proxy_port");
        } else {
            $this->addConfigOption('heartbeat_proxy', null);
        }

        $this->removeConfigOption('network_proxy_enabled');
        $this->removeConfigOption('network_proxy_protocol');
        $this->removeConfigOption('network_proxy_address');
        $this->removeConfigOption('network_proxy_port');
    }
}
