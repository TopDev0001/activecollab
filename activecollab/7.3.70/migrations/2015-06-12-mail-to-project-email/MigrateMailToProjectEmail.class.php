<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add mail to project email field.
 *
 * @package ActiveCollab.migrations
 */
class MigrateMailToProjectEmail extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $projects = $this->useTableForAlter('projects');

        $projects->addColumn(DBStringColumn::create('mail_to_project_email', DBStringColumn::MAX_LENGTH), 'mail_to_project_code');
        $projects->addIndex(DBIndex::create('mail_to_project_email', DBIndex::UNIQUE));

        $projects->dropColumn('mail_to_project_code');

        $this->doneUsingTables();
    }
}
