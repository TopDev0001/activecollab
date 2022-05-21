<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateRemoveFortnightlyRecurrence extends AngieModelMigration
{
    public function up()
    {
        $this
            ->useTableForAlter('recurring_tasks')
                ->alterColumn(
                    'repeat_frequency',
                    new DBEnumColumn(
                        'repeat_frequency',
                        [
                            'never',
                            'daily',
                            'weekly',
                            'monthly',
                            'quarterly',
                            'semiyearly',
                            'yearly',
                        ],
                        'never'
                    )
                );
    }
}
