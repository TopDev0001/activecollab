<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Move tasks from trashed list to trash.
 *
 * @package activeCollab.modules.system
 */
class MigrateMoveTasksFromTrashedListToTrash extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$task_lists, $tasks] = $this->useTables('task_lists', 'tasks');

        if ($rows = $this->execute("SELECT tl.id as id, tl.is_trashed as is_trashed, tl.trashed_on as trashed_on, tl.trashed_by_id as trashed_by_id FROM $tasks as t LEFT JOIN $task_lists as tl ON t.task_list_id = tl.id WHERE tl.is_trashed = ? AND t.is_trashed = ? GROUP BY tl.id", true, false)) {
            foreach ($rows as $row) {
                $this->execute(
                    "UPDATE $tasks SET is_trashed = ?, trashed_on = ?, trashed_by_id = ? WHERE is_trashed = ? AND task_list_id = ?",
                    true,
                    $row['trashed_on'],
                    $row['trashed_by_id'],
                    false,
                    $row['id']
                );
            }
        }

        $this->doneUsingTables();
    }
}
