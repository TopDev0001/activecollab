<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddUserDailyCapacityColumn extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('users')) {
            $users = $this->useTableForAlter('users');

            if (!$users->getColumn('daily_capacity')) {
                $users->addColumn(
                    DBDecimalColumn::create('daily_capacity', 12, 2),
                    'avatar_location'
                );
            }

            $this->doneUsingTables();
        }
    }
}
