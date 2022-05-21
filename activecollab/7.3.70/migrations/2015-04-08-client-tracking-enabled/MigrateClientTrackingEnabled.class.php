<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate client tracking enabled.
 *
 * @package ActiveCollab.migrations
 */
class MigrateClientTrackingEnabled extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('default_is_client_reporting_enabled', false);
    }
}
