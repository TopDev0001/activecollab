<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Globalization;

use DateValue;
use InvalidArgumentException;

class WorkdayResolver implements WorkdayResolverInterface
{
    private $workdays;

    public function __construct(array $workdays)
    {
        if (count($workdays) > 7) {
            throw new InvalidArgumentException('Valid list of workdays expected.');
        }

        foreach ($workdays as $workday) {
            if (!is_int($workday) || $workday < 0 || $workday > 6) {
                throw new InvalidArgumentException('Valid list of workdays expected.');
            }
        }

        $this->workdays = $workdays;
    }

    public function getWorkdays(): array
    {
        return $this->workdays;
    }

    public function isWorkday(DateValue $date): bool
    {
        return in_array($date->getWeekday(), $this->workdays);
    }

    public function isWeekend(DateValue $date): bool
    {
        return !$this->isWorkday($date);
    }
}
