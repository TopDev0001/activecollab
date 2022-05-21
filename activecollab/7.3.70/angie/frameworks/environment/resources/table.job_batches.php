<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

return DB::createTable('job_batches')->addColumns(
    [
        new DBIdColumn(),
        new DBStringColumn('name'),
        (new DBIntegerColumn('jobs_count', 10, 0))
            ->setUnsigned(true),
        new DBDateTimeColumn('created_at'),
    ]
)->addIndices(
    [
        new DBIndex('created_at'),
    ]
);
