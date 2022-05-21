<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate task discussion relation.
 *
 * @package ActiveCollab.migrations
 */
class MigrateTaskDiscussionRelation extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $tasks = $this->useTableForAlter('tasks');

        $tasks->addColumn(DBFkColumn::create('created_from_discussion_id'));

        $this->doneUsingTables();
    }
}
