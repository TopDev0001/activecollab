<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Make project leader optional.
 *
 * @package ActiveCollab.migrations
 */
class MigrateOptionalProjectLeader extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $projects = $this->useTableForAlter('projects');

        $projects->alterColumn('leader_id', DBFkColumn::create('leader_id'));
        $projects->dropColumn('leader_name');
        $projects->dropColumn('leader_email');

        if (!$projects->indexExists('leader_id')) {
            $projects->addIndex(DBIndex::create('leader_id'));
        }

        $this->doneUsingTables();
    }
}
