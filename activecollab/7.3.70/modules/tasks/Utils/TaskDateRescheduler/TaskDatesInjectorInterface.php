<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\TaskDateRescheduler;

use DateValue;

interface TaskDatesInjectorInterface
{
    public function injectDates(DateValue $from, DateValue $to, array &$tasks): void;
}
