<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Only projects and teams can be favorited.
 *
 * @package ActiveCollab.migrations
 */
class MigrateCleanUpFavoritesForFeather extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->execute('DELETE FROM ' . $this->useTables('favorites')[0] . " WHERE parent_type NOT IN ('Project', 'Team')");
        $this->doneUsingTables();
    }
}
