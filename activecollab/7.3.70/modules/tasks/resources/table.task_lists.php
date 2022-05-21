<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

return DB::createTable('task_lists')
    ->addColumns(
        [
            new DBIdColumn(),
            DBIntegerColumn::create('project_id', 10, 0)->setUnsigned(true),
            new DBNameColumn(150),
            new DBDateColumn('start_on'),
            new DBDateColumn('due_on'),
            new DBActionOnByColumn('completed', true),
            new DBCreatedOnByColumn(true, true),
            new DBUpdatedOnByColumn(),
            new DBTrashColumn(true),
            DBIntegerColumn::create('position', 10, 0)->setUnsigned(true),
        ]
    )->addIndices(
        [
            DBIndex::create('project_id'),
            DBIndex::create('span', DBIndex::KEY, ['start_on', 'due_on']),
            DBIndex::create('due_on'),
        ]
    );
