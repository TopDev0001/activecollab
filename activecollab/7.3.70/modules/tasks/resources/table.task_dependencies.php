<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

return DB::createTable('task_dependencies')
    ->addColumns(
        [
            new DBIdColumn(),
            DBFkColumn::create('parent_id', 0, true),
            DBFkColumn::create('child_id', 0, true),
            new DBCreatedOnColumn(),
        ]
    )->addIndices(
        [
            DBIndex::create('parent_id'),
            DBIndex::create('child_id'),
        ]
    );
