<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

return DB::createTable('subtasks')
    ->addColumns(
        [
            new DBIdColumn(),
            DBIntegerColumn::create('task_id', 10, 0)->setUnsigned(true),
            DBIntegerColumn::create('assignee_id', 10, 0)->setUnsigned(true),
            DBIntegerColumn::create('delegated_by_id', 10, 0)->setUnsigned(true),
            (new DBTextColumn('body'))
                ->setSize(DBTextColumn::BIG),
            new DBDateColumn('due_on'),
            new DBCreatedOnByColumn(true),
            new DBUpdatedOnColumn(),
            new DBActionOnByColumn('completed', true),
            DBIntegerColumn::create('position', 10, '0')->setUnsigned(true),
            new DBTrashColumn(true),
            new DBStringColumn('fake_assignee_name'),
            new DBStringColumn('fake_assignee_email'),
        ]
    )
    ->addIndices(
        [
            DBIndex::create('task_id'),
            DBIndex::create('created_on'),
            DBIndex::create('position'),
            DBIndex::create('completed_on'),
            DBIndex::create('due_on'),
            DBIndex::create('assignee_id'),
            DBIndex::create('delegated_by_id'),
        ]
    );
