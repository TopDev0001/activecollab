<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateMigrateMyWorkTasksGroupedBy extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('my_work_tasks_grouped_by');
    }
}
