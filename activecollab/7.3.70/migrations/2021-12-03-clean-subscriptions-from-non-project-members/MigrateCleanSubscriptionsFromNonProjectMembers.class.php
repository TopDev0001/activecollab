<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use Angie\Inflector;

class MigrateCleanSubscriptionsFromNonProjectMembers extends AngieModelMigration
{
    public function up()
    {
        $owner_ids = $this->executeFirstColumn('SELECT id FROM users WHERE type = ?', Owner::class);

        $classes = [Task::class, Note::class, Discussion::class, RecurringTask::class];

        $query = '
            DELETE s
            FROM subscriptions AS s
            INNER JOIN %s AS t ON s.parent_id = t.id
            LEFT JOIN project_users AS pu ON pu.user_id = s.user_id AND pu.project_id = t.project_id
            WHERE
             s.parent_type = ?
             AND s.user_id NOT IN (?)
             AND pu.user_id IS NULL
         ';

        foreach ($classes as $class) {
            $this->execute(sprintf($query, Inflector::pluralize(Inflector::underscore($class))), $class, $owner_ids ?: []);
        }

        $reminders_query = '
            DELETE s
            FROM subscriptions AS s
            INNER JOIN reminders AS r ON s.parent_id = r.id AND r.parent_type = ?     
            INNER JOIN tasks AS t ON r.parent_id = t.id
            LEFT JOIN project_users AS pu ON pu.user_id = s.user_id AND pu.project_id = t.project_id
            WHERE
             s.parent_type = ?
             AND s.user_id NOT IN (?)
             AND pu.user_id IS NULL
        ';

        $this->execute($reminders_query, Task::class, CustomReminder::class, $owner_ids ?: []);
    }
}
