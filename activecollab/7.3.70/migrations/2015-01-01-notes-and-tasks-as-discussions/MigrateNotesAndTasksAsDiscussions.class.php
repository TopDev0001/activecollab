<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Introduce a labs option that lets people turn on commented notes and tasks as discussions.
 *
 * @package ActiveCollab.migrations
 */
class MigrateNotesAndTasksAsDiscussions extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('notes_and_tasks_as_discussions_enabled', false);
    }
}
