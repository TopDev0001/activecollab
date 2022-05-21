<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateCleanUpOrphanStopwatches extends AngieModelMigration
{
    public function up()
    {
        $this->execute(
            'DELETE FROM `stopwatches` WHERE `parent_type` = ? AND `parent_id` NOT IN (SELECT `id` FROM `projects`)',
            Project::class,
        );

        $this->execute(
            'DELETE FROM `stopwatches` WHERE `parent_type` = ? AND `parent_id` NOT IN (SELECT `id` FROM `tasks`)',
            Task::class,
        );
    }
}
