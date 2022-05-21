<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove client notifications for hidden tasks.
 *
 * @package activeCollab.modules.system
 */
class MigrateRemoveClientNotificationsForHiddenTasks extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$notification_recipients, $notifications, $users, $tasks] = $this->useTables('notification_recipients', 'notifications', 'users', 'tasks');

        if ($notification_recipient_ids = $this->executeFirstColumn("SELECT nr.id FROM $notification_recipients as nr LEFT JOIN $notifications as n ON n.id = nr.notification_id LEFT JOIN $users as u ON u.id = nr.recipient_id LEFT JOIN $tasks as t ON t.id = n.parent_id WHERE n.parent_type = ? AND u.type = ? AND t.is_hidden_from_clients = ?", 'Task', 'Client', true)) {
            $this->execute("DELETE FROM $notification_recipients WHERE id IN (?)", $notification_recipient_ids);
        }

        $this->doneUsingTables();
    }
}
