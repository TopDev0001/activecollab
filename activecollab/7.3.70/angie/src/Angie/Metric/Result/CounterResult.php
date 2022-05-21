<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Metric\Result;

use DateValue;
use InvalidArgumentException;

class CounterResult extends Result
{
    public function __construct(string $name, int $value, DateValue $date)
    {
        if (!is_int($value)) {
            throw new InvalidArgumentException('Counter value must be an integer');
        }

        parent::__construct($name, $value, $date);
    }
}
