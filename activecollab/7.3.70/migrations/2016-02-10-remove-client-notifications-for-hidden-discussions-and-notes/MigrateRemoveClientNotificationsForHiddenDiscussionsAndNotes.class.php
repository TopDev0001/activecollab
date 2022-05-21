<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateRemoveClientNotificationsForHiddenDiscussionsAndNotes extends AngieModelMigration
{
    public function up()
    {
        [$notification_recipients, $notifications, $users, $discussions, $notes] = $this->useTables(
            'notification_recipients', 'notifications', 'users', 'discussions', 'notes'
        );

        foreach (['Discussion' => $discussions, 'Note' => $notes] as $class => $table) {
            if ($notification_recipient_ids = $this->executeFirstColumn("SELECT nr.id FROM $notification_recipients as nr LEFT JOIN $notifications as n ON n.id = nr.notification_id LEFT JOIN $users as u ON u.id = nr.recipient_id LEFT JOIN $table as t ON t.id = n.parent_id WHERE n.parent_type = ? AND u.type = ? AND t.is_hidden_from_clients = ?", $class, 'Client', true)) {
                $this->execute("DELETE FROM $notification_recipients WHERE id IN (?)", $notification_recipient_ids);
            }
        }

        $this->doneUsingTables();
    }
}
