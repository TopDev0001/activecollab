<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Extend file location field.
 *
 * @package angie.migrations
 */
class MigrateExtendFileLocationField extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        foreach (['attachments', 'files'] as $table_name) {
            $table = $this->useTableForAlter($table_name);
            $table->alterColumn('location', DBStringColumn::create('location', 255));
        }

        $this->doneUsingTables();
    }
}
