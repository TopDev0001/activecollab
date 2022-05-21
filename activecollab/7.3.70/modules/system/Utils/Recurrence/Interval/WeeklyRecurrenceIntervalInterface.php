<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Recurrence\Interval;

interface WeeklyRecurrenceIntervalInterface extends RecurrenceIntervalInterface
{
    const EVERY_WEEK = 0;
    const ON_ODD_WEEKS = 1;
    const ON_EVEN_WEEKS = 2;
}
