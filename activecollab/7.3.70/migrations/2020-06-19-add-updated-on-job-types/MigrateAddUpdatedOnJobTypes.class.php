<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddUpdatedOnJobTypes extends AngieModelMigration
{
    public function up()
    {
        $custom_hourly_rates = $this->useTableForAlter('custom_hourly_rates');
        if (!$custom_hourly_rates->getColumn('updated_on')) {
            $custom_hourly_rates->addColumn(new DBUpdatedOnColumn(), 'hourly_rate');
            $this->execute("UPDATE {$custom_hourly_rates->getName()} SET updated_on = UTC_TIMESTAMP()");
        }
        $this->doneUsingTables();
    }
}
