<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop update_history table.
 *
 * @package angie.migrations
 */
class MigrateDropUpdateHistory extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->dropTable('update_history');
    }
}
