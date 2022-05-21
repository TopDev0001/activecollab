<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove notification override settings.
 *
 * @package angie.migrations
 */
class MigrateNotificationOverrideSettings extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('who_can_override_channel_settings');
        $this->removeConfigOption('notifications_user_email_address');
        $this->removeConfigOption('notifications_user_send_email_mentions');
        $this->removeConfigOption('notifications_user_send_email_assignments');
        $this->removeConfigOption('notifications_user_send_email_subscriptions');
    }
}
