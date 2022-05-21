<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add notifications_notify_email_sender configuration option.
 *
 * @package angie.migrations
 */
class MigrateNotifyEmailSender extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('notifications_notify_email_sender', true);
    }
}
