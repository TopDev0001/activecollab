<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop project slug field.
 *
 * @package ActiveCollab.migrations
 */
class MigrateDropProjectSlug extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->execute('DELETE FROM ' . $this->useTables('modification_log_values')[0] . " WHERE field = 'slug'");
        $this->useTableForAlter('projects')->dropColumn('slug');

        $this->execute('TRUNCATE TABLE ' . $this->useTables('routing_cache')[0]);

        $this->doneUsingTables();
    }
}
