<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate bookmark and YouTube video notifications.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateBookmarkAndYoutubeNotifications extends AngieModelMigration
{
    /**
     * Delete old notifications.
     */
    public function up()
    {
        $notification_ids = $this->executeFirstColumn('SELECT id FROM notifications WHERE type IN (?) OR parent_type IN (?)', ['NewBookmarkNotification', 'NewYouTubeVideoNotification'], ['Bookmark', 'YouTubeVideo']);

        if ($notification_ids) {
            $this->execute('DELETE FROM notification_recipients WHERE notification_id IN (?)', $notification_ids);
            $this->execute('DELETE FROM notifications WHERE id IN (?)', $notification_ids);
        }
    }
}
