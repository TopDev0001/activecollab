<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateDeleteStopwatchesWhenParentIsDeleted extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('stopwatches')) {
            $this->execute('DELETE FROM stopwatches WHERE parent_type = "Task" AND parent_id NOT IN (SELECT id FROM tasks)');
        }
    }
}
