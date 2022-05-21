<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\TaskDateRescheduler;

use DateValue;
use Task;

class SkippableTaskDatesCorrector implements TaskDatesCorrectorInterface
{
    private $corrector;
    private $skip_days_off_resolver;

    public function __construct(
        TaskDatesCorrectorInterface $corrector,
        callable $skip_days_off_resolver
    )
    {
        $this->corrector = $corrector;
        $this->skip_days_off_resolver = $skip_days_off_resolver;
    }

    public function correctDates(Task $task, DateValue &$start_on, DateValue &$due_on): void
    {
        if (call_user_func($this->skip_days_off_resolver)) {
            $this->corrector->correctDates($task, $start_on, $due_on);
        }
    }
}
