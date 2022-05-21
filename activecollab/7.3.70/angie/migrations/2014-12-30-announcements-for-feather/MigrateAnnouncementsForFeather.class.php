<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Simplify announcements model.
 *
 * @package angie.migrations
 */
class MigrateAnnouncementsForFeather extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->dropTable('announcements');
        $this->dropTable('announcement_target_ids');
        $this->dropTable('announcement_dismissals');
    }
}
