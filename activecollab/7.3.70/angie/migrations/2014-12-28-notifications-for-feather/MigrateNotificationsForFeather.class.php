<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate notifications for feather.
 *
 * @package angie.migrations
 */
class MigrateNotificationsForFeather extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('notifications_show_indicators');
        $this->removeConfigOption('popup_show_only_unread');

        $this->addConfigOption('notifications_fetched_on');

        $notification_recipients = $this->useTableForAlter('notification_recipients');

        $notification_recipients->dropColumn('seen_on');
        $notification_recipients->dropColumn('read_on');

        $this->doneUsingTables();
    }
}
