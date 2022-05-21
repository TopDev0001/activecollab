<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop support for notes and tasks to be listed in discussions collection.
 *
 * @package ActiveCollab.migrations
 */
class MigrateDropNotesAndTasksAsDiscussions extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('notes_and_tasks_as_discussions_enabled');
    }
}
