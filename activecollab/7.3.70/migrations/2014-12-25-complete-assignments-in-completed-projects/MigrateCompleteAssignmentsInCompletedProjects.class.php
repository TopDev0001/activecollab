<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Complete all open assignments in completed projects.
 *
 * @package ActiveCollab.migrations
 */
class MigrateCompleteAssignmentsInCompletedProjects extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$projects, $task_lists, $tasks, $subtasks] = $this->useTables('projects', 'task_lists', 'tasks', 'subtasks');

        if ($rows = $this->execute("SELECT id, completed_on, completed_by_id, completed_by_name, completed_by_email FROM $projects WHERE completed_on IS NOT NULL")) {
            foreach ($rows as $row) {
                $this->execute("UPDATE $tasks SET completed_on = ?, completed_by_id = ?, completed_by_name = ?, completed_by_email = ?, updated_on = ? WHERE project_id = ? AND completed_on IS NULL", $row['completed_on'], $row['completed_by_id'], $row['completed_by_name'], $row['completed_by_email'], $row['completed_on'], $row['id']);
                $this->execute("UPDATE $task_lists SET completed_on = ?, completed_by_id = ?, completed_by_name = ?, completed_by_email = ?, updated_on = ? WHERE project_id = ? AND completed_on IS NULL", $row['completed_on'], $row['completed_by_id'], $row['completed_by_name'], $row['completed_by_email'], $row['completed_on'], $row['id']);
                $this->execute("UPDATE $subtasks SET completed_on = ?, completed_by_id = ?, completed_by_name = ?, completed_by_email = ?, updated_on = ? WHERE task_id IN (SELECT id FROM $tasks WHERE project_id = ?) AND completed_on IS NULL", $row['completed_on'], $row['completed_by_id'], $row['completed_by_name'], $row['completed_by_email'], $row['completed_on'], $row['id']);
            }
        }

        $this->doneUsingTables();
    }
}
