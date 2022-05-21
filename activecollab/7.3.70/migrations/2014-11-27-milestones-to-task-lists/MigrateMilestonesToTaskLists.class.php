<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate milestones to task lists.
 */
class MigrateMilestonesToTaskLists extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $tasks = $this->useTableForAlter('tasks');
        $this->execute('UPDATE ' . $tasks->getName() . ' SET milestone_id = 0 WHERE milestone_id IS NULL');

        $tasks->alterColumn('milestone_id', DBFkColumn::create('task_list_id'));

        $this->renameTable('milestones', 'task_lists');

        // ---------------------------------------------------
        //  Logs and relations
        // ---------------------------------------------------

        foreach ($this->useTables('access_logs', 'activity_logs', 'favorites', 'subscriptions') as $table) {
            $this->execute("DELETE FROM $table WHERE parent_type = 'Milestone'");
        }

        $this->execute('UPDATE ' . $this->useTables('modification_logs')[0] . " SET parent_type = 'TaskList' WHERE parent_type = 'Milestone'");
        $this->execute('UPDATE ' . $this->useTables('modification_log_values')[0] . " SET field = 'task_list_id' WHERE field = 'milestone_id'");

        // ---------------------------------------------------
        //  Template elements
        // ---------------------------------------------------

        $template_elements_table = $this->useTables('project_template_elements')[0];

        $this->execute("UPDATE $template_elements_table SET type = 'ProjectTemplateTaskList' WHERE type = 'ProjectTemplateMilestone'");

        if ($rows = $this->execute("SELECT id, raw_additional_properties FROM $template_elements_table WHERE type = 'ProjectTemplateTask'")) {
            foreach ($rows as $row) {
                $attributes = $row['raw_additional_properties'] ? unserialize($row['raw_additional_properties']) : [];

                if (array_key_exists('milestone_id', $attributes)) {
                    $attributes['task_list_id'] = $attributes['milestone_id'];
                    unset($attributes['milestone_id']);

                    $this->execute("UPDATE $template_elements_table SET raw_additional_properties = ? WHERE id = ?", serialize($attributes), $row['id']);
                }
            }
        }

        $this->doneUsingTables();
    }
}
