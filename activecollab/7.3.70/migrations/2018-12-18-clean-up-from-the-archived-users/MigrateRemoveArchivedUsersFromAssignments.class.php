<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateRemoveArchivedUsersFromAssignments extends AngieModelMigration
{
    public function up()
    {
        [
            $tasks_table,
            $recurring_tasks_table,
            $subtasks_table,
            $users_table,
            $project_users_table,
            $project_template_users_table,
            $project_template_elements_table
        ] = $this->useTables(
            'tasks',
            'recurring_tasks',
            'subtasks',
            'users',
            'project_users',
            'project_template_users',
            'project_template_elements'
        );

        if ($user_ids = $this->executeFirstColumn("SELECT id FROM $users_table WHERE is_archived = ?", true)) {
            $this->execute("UPDATE $tasks_table SET assignee_id = 0 WHERE assignee_id IN (?)", $user_ids);
            $this->execute("UPDATE $subtasks_table SET assignee_id = 0 WHERE assignee_id IN (?)", $user_ids);
            $this->execute("UPDATE $recurring_tasks_table SET assignee_id = 0 WHERE assignee_id IN (?)", $user_ids);

            // Find all projects where those users are members.
            $project_ids = $this->executeFirstColumn("SELECT project_id FROM $project_users_table WHERE user_id IN (?)", $user_ids);

            if ($project_ids) {
                // Find all recurring tasks in those projects.
                $recurring_tasks = $this->execute("SELECT id, raw_additional_properties FROM $recurring_tasks_table WHERE project_id IN (?)", $project_ids);

                if ($recurring_tasks) {
                    foreach ($recurring_tasks as $recurring_task) {
                        $additional_properties = $recurring_task['raw_additional_properties'] ? unserialize($recurring_task['raw_additional_properties']) : null;

                        if (empty($additional_properties)) {
                            $additional_properties = [];
                        }

                        if (
                            isset($additional_properties['recurring_subtasks']) &&
                            is_array($additional_properties['recurring_subtasks'])
                        ) {
                            $save = false;

                            foreach ($additional_properties['recurring_subtasks'] as &$recurring_subtask) {
                                if (
                                    isset($recurring_subtask['assignee_id']) &&
                                    !empty($recurring_subtask['assignee_id']) &&
                                    in_array($recurring_subtask['assignee_id'], $user_ids)
                                ) {
                                    $recurring_subtask['assignee_id'] = 0;
                                    $save = true;
                                }
                            }

                            if ($save) {
                                $this->execute("UPDATE $recurring_tasks_table SET raw_additional_properties = ? WHERE id = ?", serialize($additional_properties), $recurring_task['id']);
                            }
                        }
                    }
                }
            }

            // Find all templates where those users are members.
            $template_ids = $this->executeFirstColumn("SELECT project_template_id FROM $project_template_users_table WHERE user_id IN (?)", $user_ids);

            if ($template_ids) {
                $elements = $this->execute("SELECT id, raw_additional_properties FROM $project_template_elements_table WHERE template_id IN (?)", $template_ids);

                foreach ($elements as $element) {
                    $additional_properties = $element['raw_additional_properties'] ? unserialize($element['raw_additional_properties']) : null;

                    if (empty($additional_properties)) {
                        $additional_properties = [];
                    }

                    $save = false;

                    if (
                        isset($additional_properties['subtasks']) &&
                        is_array($additional_properties['subtasks'])
                    ) {
                        foreach ($additional_properties['subtasks'] as &$subtask) {
                            if (
                                isset($subtask['assignee_id']) &&
                                !empty($subtask['assignee_id']) &&
                                in_array($subtask['assignee_id'], $user_ids)
                            ) {
                                $subtask['assignee_id'] = 0;
                                $save = true;
                            }
                        }
                    }

                    if (
                        isset($additional_properties['assignee_id']) &&
                        !empty($additional_properties['assignee_id']) &&
                        in_array($additional_properties['assignee_id'], $user_ids)
                    ) {
                        $additional_properties['assignee_id'] = 0;
                        $save = true;
                    }

                    if ($save) {
                        $this->execute("UPDATE $project_template_elements_table SET raw_additional_properties = ? WHERE id = ?", serialize($additional_properties), $element['id']);
                    }
                }
            }
        }

        $this->doneUsingTables();
    }
}
