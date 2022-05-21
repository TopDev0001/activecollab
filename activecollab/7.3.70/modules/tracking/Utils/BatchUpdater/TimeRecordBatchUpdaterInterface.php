<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Utils\BatchUpdater;

use User;

interface TimeRecordBatchUpdaterInterface
{
    const SUPPORTED_FIELDS = [
        'job_type_id',
        'record_date',
        'user_id',
        'billable_status',
    ];

    public function batchUpdate(
        array $attributes,
        User $by,
        int ...$time_record_ids
    ): array;
}
