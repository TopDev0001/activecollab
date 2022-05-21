<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddBudgetThresholdsNotificationsModel extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('budget_thresholds_notifications')) {
            $this->createTable(
                DB::createTable('budget_thresholds_notifications')
                    ->addColumns(
                        [
                            new DBIdColumn(),
                            DBIntegerColumn::create('parent_id', 10, 0)->setUnsigned(true),
                            DBIntegerColumn::create('user_id', 10, 0)->setUnsigned(true),
                            new DBDateTimeColumn('sent_at'),
                        ]
                    )
            );
        }
    }
}
