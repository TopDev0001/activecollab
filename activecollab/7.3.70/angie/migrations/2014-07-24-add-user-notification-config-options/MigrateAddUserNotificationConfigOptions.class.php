<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add config options for user notifications.
 *
 * @package angie.migrations
 */
class MigrateAddUserNotificationConfigOptions extends AngieModelMigration
{
    /**
     * Upgrade the data.
     */
    public function up()
    {
        $this->addConfigOption('notifications_user_email_address', null);
        $this->addConfigOption('notifications_user_send_email_mentions', true);
        $this->addConfigOption('notifications_user_send_email_assignments', true);
        $this->addConfigOption('notifications_user_send_email_subscriptions', true);

        $this->renameConfigOption('morning_paper_enabled', 'notifications_user_send_morning_paper');
    }
}
