<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Update bookmark and youtube video favorites.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateBookmarkYouTubeFavorites extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->execute('UPDATE favorites SET parent_type = ? WHERE parent_type IN (?)', 'Discussion', ['Bookmark', 'YouTubeVideo']);
    }
}
