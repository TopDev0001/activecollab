<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateLogOpenSubtasksOnClosedTasksAndProjects extends AngieModelMigration
{
    public function up()
    {
        $open_subtasks_in_closed_projects = $this->executeFirstCell(
            'SELECT COUNT(s.id) as count
            FROM subtasks s
            LEFT JOIN tasks t ON t.id = s.task_id
            LEFT JOIN projects p ON p.id = t.project_id
            WHERE p.completed_on IS NOT NULL AND t.completed_on IS NOT NULL AND s.completed_on IS NULL'
        );

        $open_subtasks_in_closed_tasks = $this->executeFirstCell(
            'SELECT COUNT(s.id) as count
            FROM subtasks s
            LEFT JOIN tasks t ON t.id = s.task_id
            LEFT JOIN projects p ON p.id = t.project_id
            WHERE p.completed_on IS NULL AND t.completed_on IS NOT NULL AND s.completed_on IS NULL'
        );

        AngieApplication::log()->info(
            'Open subtasks count in completed project and tasks.',
            [
                'open_subtasks_in_closed_projects' => $open_subtasks_in_closed_projects,
                'open_subtasks_in_closed_tasks' => $open_subtasks_in_closed_tasks,
            ]
        );
    }
}
