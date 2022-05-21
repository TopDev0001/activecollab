<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Make sure that email channel is off by default.
 *
 * @package angie.migrations
 */
class MigrateEmailChannelOffByDefault extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('email_notifications_enabled');
    }
}
