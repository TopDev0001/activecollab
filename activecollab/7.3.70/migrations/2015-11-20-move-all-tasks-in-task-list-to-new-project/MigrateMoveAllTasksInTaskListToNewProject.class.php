<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Move all tasks in task list to new project.
 *
 * @package activeCollab.modules.system
 */
class MigrateMoveAllTasksInTaskListToNewProject extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$task_lists, $tasks] = $this->useTables('task_lists', 'tasks');

        if ($rows = DB::execute("SELECT tl.id as id, tl.project_id as project_id FROM $tasks as t LEFT JOIN $task_lists as tl ON tl.id = t.task_list_id WHERE t.project_id != tl.project_id GROUP BY tl.id")) {
            foreach ($rows as $row) {
                $next_task_number = DB::executeFirstCell("SELECT MAX(task_number) FROM $tasks WHERE project_id = ?", $row['project_id']) + 1;

                if ($task_ids = DB::executeFirstColumn("SELECT id FROM $tasks WHERE task_list_id = ? AND project_id != ?", $row['id'], $row['project_id'])) {
                    foreach ($task_ids as $task_id) {
                        DB::execute(
                            "UPDATE $tasks SET project_id = ?, task_number = ? WHERE id = ?",
                            $row['project_id'],
                            $next_task_number++,
                            $task_id
                        );
                    }
                }
            }
        }

        $this->doneUsingTables();
    }
}
