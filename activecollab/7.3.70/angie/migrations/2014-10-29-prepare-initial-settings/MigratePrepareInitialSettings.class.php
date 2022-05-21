<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Prepare initial settings.
 *
 * @package angie.migrations
 */
class MigratePrepareInitialSettings extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('initial_settings_timestamp', time());
    }
}
