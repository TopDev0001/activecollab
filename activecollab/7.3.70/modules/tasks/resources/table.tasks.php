<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

return DB::createTable('tasks')
    ->addColumns(
        [
            new DBIdColumn(),
            DBFkColumn::create('project_id', 0, true),
            DBIntegerColumn::create('task_number', 10, 0)->setUnsigned(true),
            DBFkColumn::create('task_list_id', 0, true),
            DBFkColumn::create('assignee_id', 0, true),
            DBFkColumn::create('delegated_by_id', 0, true),
            DBFkColumn::create('created_from_recurring_task_id', 0, true),
            new DBNameColumn(150),
            new DBBodyColumn(),
            new DBBoolColumn('is_important'),
            new DBCreatedOnByColumn(true, true),
            new DBUpdatedOnByColumn(),
            new DBDateColumn('start_on'),
            new DBDateColumn('due_on'),
            DBFkColumn::create('job_type_id')->setSize(DBColumn::SMALL),
            DBDecimalColumn::create('estimate', 12, 2, 0)->setUnsigned(true),
            new DBActionOnByColumn('completed'),
            DBIntegerColumn::create('position', 10, 0)->setUnsigned(true),
            new DBBoolColumn('is_hidden_from_clients'),
            new DBBoolColumn('is_billable', true),
            new DBTrashColumn(true),
            DBStringColumn::create('fake_assignee_name'),
            DBStringColumn::create('fake_assignee_email'),
        ]
    )->addIndices(
        [
            DBIndex::create(
                'project_task_number',
                DBIndex::UNIQUE,
                [
                    'project_id',
                    'task_number',
                ]
            ),
            DBIndex::create('task_number'),
            DBIndex::create('start_on'),
            DBIndex::create('due_on'),
        ]
    );
