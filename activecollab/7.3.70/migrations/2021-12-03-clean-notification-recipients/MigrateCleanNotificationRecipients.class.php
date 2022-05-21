<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use Angie\Inflector;

class MigrateCleanNotificationRecipients extends AngieModelMigration
{
    public function up()
    {
        $classes = [Task::class, Note::class, Discussion::class];
        $owner_ids = $this->executeFirstColumn('SELECT id FROM users WHERE type = ?', Owner::class);

        foreach ($classes as $class) {
            $table_name = Inflector::pluralize(Inflector::underscore($class));

            $this->execute("DELETE nr FROM notification_recipients nr 
                JOIN notifications n ON n.id = nr.notification_id
                JOIN {$table_name} t ON t.id = n.parent_id AND n.parent_type = ?
                LEFT JOIN project_users pu ON pu.user_id = nr.recipient_id AND pu.project_id = t.project_id
                WHERE pu.user_id IS NULL
                AND nr.recipient_id NOT IN (?)",
                $class,
                $owner_ids
            );
        }

        $this->execute('DELETE nr FROM notification_recipients nr
                JOIN notifications n ON n.id = nr.notification_id AND n.parent_type = ?
                LEFT JOIN project_users pu ON pu.user_id = nr.recipient_id AND pu.project_id = n.parent_id
                WHERE pu.user_id IS NULL
                AND nr.recipient_id NOT IN (?)',
            Project::class,
            $owner_ids
        );
    }
}
