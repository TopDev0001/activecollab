<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop notifications that are left after converted notifications.
 *
 * @package ActiveCollab.migrations
 */
class MigrateDropConvertedDiscussionNotifications extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$discussions, $tasks, $notifications, $notification_recipients] = $this->useTables('discussions', 'tasks', 'notifications', 'notification_recipients');

        if ($notification_ids = $this->executeFirstColumn("SELECT id FROM $notifications WHERE parent_type = 'Discussion' AND parent_id NOT IN (SELECT id FROM $discussions)")) {
            $this->execute("DELETE FROM $notifications WHERE id IN (?)", $notification_ids);
            $this->execute("DELETE FROM $notification_recipients WHERE notification_id IN (?)", $notification_ids);
        }

        if ($notification_ids = $this->executeFirstColumn("SELECT id FROM $notifications WHERE parent_type = 'Task' AND parent_id NOT IN (SELECT id FROM $tasks)")) {
            $this->execute("DELETE FROM $notifications WHERE id IN (?)", $notification_ids);
            $this->execute("DELETE FROM $notification_recipients WHERE notification_id IN (?)", $notification_ids);
        }

        $this->doneUsingTables();
    }
}
