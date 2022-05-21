<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Update positions on project template tasks.
 *
 * @package ActiveCollab.migrations
 */
class MigrateUpdatePositionsOnProjectTemplateTasks extends AngieModelMigration
{
    /**
     * Update positions.
     */
    public function up()
    {
        $project_template_elements = $this->useTables('project_template_elements')[0];

        if ($rows = DB::execute("SELECT id, template_id FROM $project_template_elements WHERE type = ? AND (position = ? OR position IS NULL) ORDER BY id", 'ProjectTemplateTask', 0)) {
            $map_template_id_position = [];

            foreach ($rows as $row) {
                if (!isset($map_template_id_position[$row['template_id']])) {
                    $map_template_id_position[$row['template_id']] = DB::executeFirstCell("SELECT MAX(position) FROM $project_template_elements WHERE type = ? AND template_id = ?", 'ProjectTemplateTask', $row['template_id']) + 1;
                }
                DB::execute("UPDATE $project_template_elements SET position = ? WHERE id = ? AND template_id = ?", $map_template_id_position[$row['template_id']]++, $row['id'], $row['template_id']);
            }
        }

        $this->doneUsingTables();
    }
}
