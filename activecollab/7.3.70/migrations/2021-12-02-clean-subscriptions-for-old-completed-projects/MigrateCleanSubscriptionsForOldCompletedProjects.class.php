<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use Angie\Inflector;

class MigrateCleanSubscriptionsForOldCompletedProjects extends AngieModelMigration
{
    public function up()
    {
        $project_ids = $this->executeFirstColumn('SELECT id FROM projects WHERE completed_on <= (NOW() - INTERVAL 90 DAY)');

        if (!$project_ids) {
            return;
        }

        $this->execute('DELETE s FROM subscriptions s
                            JOIN reminders r ON r.id = s.parent_id AND s.parent_type = ?
                            JOIN tasks t ON r.parent_type = ? AND r.parent_id = t.id
                            WHERE t.project_id IN (?)', CustomReminder::class, Task::class, $project_ids);

        $classes = [Task::class, Note::class, Discussion::class, RecurringTask::class];
        $query = "DELETE s FROM subscriptions s JOIN %s t ON s.parent_type = '%s' AND s.parent_id = t.id WHERE t.project_id IN (?)";

        foreach ($classes as $class) {
            $this->execute(sprintf($query, Inflector::pluralize(Inflector::underscore($class)), $class), $project_ids);
        }
    }
}
