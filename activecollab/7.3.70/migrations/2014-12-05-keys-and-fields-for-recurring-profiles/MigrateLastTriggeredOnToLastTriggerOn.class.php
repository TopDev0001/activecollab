<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateLastTriggeredOnToLastTriggerOn extends AngieModelMigration
{
    public function up()
    {
        $this->useTableForAlter('recurring_profiles')->alterColumn(
            'last_triggered_on', new DBDateColumn('last_trigger_on')
        );
        $this->doneUsingTables();
    }
}
