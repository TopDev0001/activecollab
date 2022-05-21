<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate license settings.
 *
 * @package angie.migrations
 */
class MigrateLicenseSettings extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->renameConfigOption('latest_version', 'heartbeat_latest_stable_version');
        $this->renameConfigOption('latest_available_version', 'heartbeat_latest_available_version');
        $this->renameConfigOption('license_copyright_removed', 'heartbeat_branding_removed');

        $this->removeConfigOption('license_details_updated_on');
        $this->removeConfigOption('license_expires');
        $this->removeConfigOption('remove_branding_url');
        $this->removeConfigOption('renew_support_url');
        $this->removeConfigOption('update_instructions_url');
        $this->removeConfigOption('update_archive_url');
    }
}
