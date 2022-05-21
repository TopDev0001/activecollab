<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate auto upgrade memories.
 *
 * @package angie.migrations
 */
class MigrateAutoUpgradeMemories extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->execute('DELETE FROM `memories` WHERE `key` IN (?)', ['auto_upgrade_latest_stable_version', 'auto_upgrade_latest_available_version', 'auto_upgrade_release_notes', 'auto_upgrade_release_warnings']);
    }
}
