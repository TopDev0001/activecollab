<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateRestoreNotificationConfigOptions extends AngieModelMigration
{
    public function up()
    {
        $this->renameConfigOption('notification_new_note', 'notifications_user_send_email_new_project_element');
        $this->setConfigOptionValue('notifications_user_send_email_new_project_element', true);

        $this->addConfigOption('notifications_user_send_email_mentions', true);
        $this->addConfigOption('notifications_user_send_email_assignments', true);
        $this->addConfigOption('notifications_user_send_email_subscriptions', true);
    }
}
