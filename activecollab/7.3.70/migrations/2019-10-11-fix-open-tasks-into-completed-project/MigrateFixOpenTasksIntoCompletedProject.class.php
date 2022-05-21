<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateFixOpenTasksIntoCompletedProject extends AngieModelMigration
{
    public function up()
    {
        DB::execute(
            'UPDATE tasks as t
            INNER JOIN projects as p ON t.project_id = p.id
            SET
            t.completed_on = p.completed_on,
            t.updated_on = p.updated_on,
            t.completed_by_name = p.completed_by_name,
            t.completed_by_email = p.completed_by_email,
            t.completed_by_id = p.completed_by_id
            WHERE
            t.completed_on IS NULL
            AND
            p.completed_on IS NOT NULL'
        );
    }
}
