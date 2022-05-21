<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate project template users to project template members.
 *
 * @package ActiveCollab.migrations
 */
class MigrateProjectTemplateUserElementsToProjectTemplateUsersTable extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->createTable(DB::createTable('project_template_users')->addColumns([
            DBFkColumn::create('user_id'),
            DBFkColumn::create('project_template_id'),
        ])->addIndices([
            new DBIndexPrimary(['user_id', 'project_template_id']),
        ]));

        [$project_template_users, $project_template_elements] = $this->useTables('project_template_users', 'project_template_elements');

        if ($user_rows = $this->execute('SELECT id, template_id, raw_additional_properties FROM ' . $project_template_elements . " WHERE type = 'ProjectTemplateUser'")) {
            $batch = new DBBatchInsert($project_template_users, ['user_id', 'project_template_id']);

            foreach ($user_rows as $user_row) {
                $attributes = $user_row['raw_additional_properties'] ? unserialize($user_row['raw_additional_properties']) : [];

                if (isset($attributes['user_id']) && $attributes['user_id']) {
                    $batch->insert($attributes['user_id'], $user_row['template_id']);
                }
            }

            $batch->done();
        }

        $this->execute('DELETE FROM ' . $project_template_elements . " WHERE type = 'ProjectTemplateUser'");

        $this->doneUsingTables();
    }
}
