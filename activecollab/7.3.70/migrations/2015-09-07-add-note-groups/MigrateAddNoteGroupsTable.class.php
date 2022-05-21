<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add note groups table.
 *
 * @package ActiveCollab.migrations
 */
class MigrateAddNoteGroupsTable extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->createTable('note_groups', [
            new DBIdColumn(),
            DBIntegerColumn::create('project_id', 10, 0)->setUnsigned(true),
            DBIntegerColumn::create('position', DBIntegerColumn::NORMAL, 0)->setUnsigned(true),
        ], [
            DBIndex::create('position'),
        ]);
    }
}
