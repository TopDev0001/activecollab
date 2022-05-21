<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate mailing configuration from database.
 *
 * @package angie.migrations
 */
class MigrateMailingConfigFromDatabase extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('mailing');
        $this->removeConfigOption('mailing_method');
        $this->removeConfigOption('mailing_method_override');
        $this->removeConfigOption('mailing_native_options');
        $this->removeConfigOption('mailing_mark_as_bulk');

        $this->removeConfigOption('conflict_notifications_users');

        $this->removeConfigOption('notifications_from_name');
        $this->removeConfigOption('notifications_from_email');
        $this->removeConfigOption('notifications_from_force');

        $this->removeConfigOption('disable_mailbox_on_successive_connection_failures');
        $this->removeConfigOption('disable_mailbox_successive_connection_attempts');
        $this->removeConfigOption('disable_mailbox_notify_administrator');
        $this->removeConfigOption('conflict_notifications_delivery');

        $this->useTableForAlter('outgoing_messages')->dropColumn('mailing_method');
        $this->doneUsingTables();
    }
}
