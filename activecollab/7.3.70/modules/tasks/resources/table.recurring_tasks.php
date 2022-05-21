<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

return DB::createTable('recurring_tasks')
    ->addColumns(
        [
            new DBIdColumn(),
            DBFkColumn::create('project_id', 0, true),
            DBFkColumn::create('task_list_id', 0, true),
            DBFkColumn::create('assignee_id', 0, true),
            DBFkColumn::create('delegated_by_id', 0, true),
            new DBNameColumn(150),
            new DBBodyColumn(),
            new DBBoolColumn('is_important'),
            new DBCreatedOnByColumn(true, true),
            new DBUpdatedOnByColumn(),
            DBIntegerColumn::create('start_in')->setUnsigned(true),
            DBIntegerColumn::create('due_in')->setUnsigned(true),
            DBFkColumn::create('job_type_id')->setSize(DBColumn::SMALL),
            DBDecimalColumn::create('estimate', 12, 2, 0)->setUnsigned(true),
            DBIntegerColumn::create('position', 10, 0)->setUnsigned(true),
            new DBBoolColumn('is_hidden_from_clients'),
            new DBTrashColumn(true),
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
            ),
            (new DBIntegerColumn('repeat_amount', 10, 0))->setUnsigned(true),
            (new DBIntegerColumn('repeat_amount_extended', 10, 0))->setUnsigned(true),
            (new DBIntegerColumn('triggered_number', 10, 0))->setUnsigned(true),
            new DBDateColumn('last_trigger_on'),
            DBStringColumn::create('fake_assignee_name'),
            DBStringColumn::create('fake_assignee_email'),
            new DBAdditionalPropertiesColumn(),
        ]
    );
