<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add default task list to projects and tasks without task list.
 *
 * @package ActiveCollab.migrations
 */
class MigrateAddDefaultTaskListToProjects extends AngieModelMigration
{
    /**
     * Let the system add default task list name config option before we get into this.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateAddDefaultTaskListNameConfigOption');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        [$projects, $task_lists, $tasks] = $this->useTables('projects', 'task_lists', 'tasks');

        $task_list_name = $this->getConfigOptionValue('default_task_list_name');
        $owner = $this->getFirstUsableOwner();

        $project_ids = $this->executeFirstColumn("SELECT id FROM $projects");

        if (is_foreachable($project_ids)) {
            $this->execute("UPDATE $task_lists SET position = position + 1");

            foreach ($project_ids as $project_id) {
                $this->execute("INSERT INTO $task_lists (project_id, name, created_on, created_by_id, created_by_name, created_by_email, updated_on, position) VALUES (?, ?, UTC_TIMESTAMP(), ?, ?, ?, UTC_TIMESTAMP(), ?)", $project_id, $task_list_name, $owner[0], $owner[1], $owner[2], 1);

                $task_list_id = $this->lastInsertId();

                $this->execute("UPDATE $tasks SET task_list_id = ? WHERE project_id = ? AND task_list_id = 0", $task_list_id, $project_id);
            }
        }

        $this->doneUsingTables();
    }
}
