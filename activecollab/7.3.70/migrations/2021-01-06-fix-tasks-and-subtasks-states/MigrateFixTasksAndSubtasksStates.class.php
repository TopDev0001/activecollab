<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateFixTasksAndSubtasksStates extends AngieModelMigration
{
    public function up()
    {
        $open_subtasks_in_closed_projects = $this->execute(
            'SELECT s.id, t.completed_on
            FROM subtasks s
            LEFT JOIN tasks t ON t.id = s.task_id
            LEFT JOIN projects p ON p.id = t.project_id
            WHERE p.completed_on IS NOT NULL AND t.completed_on IS NOT NULL AND s.completed_on IS NULL'
        );

        if (!empty($open_subtasks_in_closed_projects)) {
            $when_then_cases = '';
            $subtask_ids = [];

            foreach ($open_subtasks_in_closed_projects as $row) {
                $subtask_ids[] = $row['id'];
                $when_then_cases .= "WHEN {$row['id']} THEN '{$row['completed_on']}' ";
            }

            $this->execute(
                "UPDATE `subtasks`
                        SET `updated_on` = UTC_TIMESTAMP(), `completed_on` = (CASE `id` {$when_then_cases} END)
                        WHERE `id` IN (?)",
                $subtask_ids
            );
        }

        $open_subtasks_in_closed_tasks_ids = $this->executeFirstCell(
            'SELECT t.id
            FROM subtasks s
            LEFT JOIN tasks t ON t.id = s.task_id
            LEFT JOIN projects p ON p.id = t.project_id
            WHERE p.completed_on IS NULL AND t.completed_on IS NOT NULL AND s.completed_on IS NULL'
        );

        if (!empty($open_subtasks_in_closed_tasks_ids)) {
            $this->execute(
                'UPDATE `tasks`
                        SET `updated_on` = UTC_TIMESTAMP(), `completed_on` = NULL, `completed_by_id` = NULL, `completed_by_name` = NULL, `completed_by_email` = NULL
                        WHERE `id` IN (?)',
                $open_subtasks_in_closed_tasks_ids
            );
        }
    }
}
