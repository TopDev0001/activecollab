<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

AngieApplication::useModel(
    [
        'budget_thresholds',
        'budget_thresholds_notifications',
        'expense_categories',
        'expenses',
        'job_types',
        'stopwatches',
        'time_records',
        'user_internal_rates',
    ],
    'tracking'
);
