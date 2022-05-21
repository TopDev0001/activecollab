<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateFixSubtasksAssignees extends AngieModelMigration
{
    public function up()
    {
        $user_ids = $this->executeFirstColumn('SELECT id FROM users');
        $subtasks_user_ids = $this->executeFirstColumn('SELECT DISTINCT assignee_id FROM subtasks WHERE assignee_id > 0');

        if (!empty($user_ids) && !empty($subtasks_user_ids)) {
            $non_existing_user_ids = array_diff($subtasks_user_ids, $user_ids);

            if (count($non_existing_user_ids)) {
                $this->execute(
                    'UPDATE subtasks SET assignee_id = 0 WHERE assignee_id IN (?)',
                    $non_existing_user_ids
                );
            }
        }
    }
}
