<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add completed on key to projects model.
 *
 * @package ActiveCollab.migrations
 */
class MigrateCompletedOnKeyForProjects extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $projects = $this->useTableForAlter('projects');

        if (!$projects->getIndex('completed_on')) {
            $projects->addIndex(DBIndex::create('completed_on'));
        }

        $this->doneUsingTables();
    }
}
