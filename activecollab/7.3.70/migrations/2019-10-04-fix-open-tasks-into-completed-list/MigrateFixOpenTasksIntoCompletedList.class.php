<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateFixOpenTasksIntoCompletedList extends AngieModelMigration
{
    public function up()
    {
        $task_list_ids = DB::executeFirstColumn(
            'SELECT DISTINCT tl.id FROM tasks t
            LEFT JOIN task_lists tl ON tl.id = t.task_list_id
            WHERE t.completed_on IS NULL AND tl.completed_on IS NOT NULL'
        );

        if (is_array($task_list_ids) && count($task_list_ids)) {
            DB::execute(
                'UPDATE task_lists
                SET completed_on = NULL, completed_by_id = NULL, completed_by_name = NULL, completed_by_email = NULL, updated_on = UTC_TIMESTAMP()
                WHERE id IN (?)',
                $task_list_ids
            );
        }
    }
}
