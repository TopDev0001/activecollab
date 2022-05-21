<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddNewRecurringTaskIntervals extends AngieModelMigration
{
    public function up()
    {
        $recurring_tasks = $this->useTableForAlter('recurring_tasks');

        $recurring_tasks->alterColumn(
            'repeat_frequency',
            new DBEnumColumn(
                'repeat_frequency',
                [
                    'never',
                    'daily',
                    'weekly',
                    'fortnightly',
                    'monthly',
                    'quarterly',
                    'semiyearly',
                    'yearly',
                ],
                'never'
            )
        );
        $recurring_tasks->addColumn(
            (new DBIntegerColumn('repeat_amount_extended', 10, 0))->setUnsigned(true),
            'repeat_amount'
        );
    }
}
