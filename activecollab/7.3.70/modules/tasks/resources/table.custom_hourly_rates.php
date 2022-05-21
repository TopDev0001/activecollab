<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

return DB::createTable('custom_hourly_rates')
    ->addColumns(
        [
            new DBParentColumn(true, false),
            (new DBIntegerColumn('job_type_id', DBColumn::NORMAL, 0))
                ->setUnsigned(true),
            (new DBMoneyColumn('hourly_rate', 0))
                ->setUnsigned(true),
            new DBUpdatedOnColumn(),
        ]
    )
    ->addIndices(
        [
            new DBIndexPrimary(
                [
                    'parent_type',
                    'parent_id',
                    'job_type_id',
                ]
            ),
        ]
    );
