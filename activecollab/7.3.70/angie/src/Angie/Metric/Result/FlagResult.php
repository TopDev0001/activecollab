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

class FlagResult extends Result
{
    public function __construct(string $name, bool $value, DateValue $date)
    {
        if (!is_bool($value)) {
            throw new InvalidArgumentException('Flag value must be boolean');
        }

        parent::__construct($name, $value, $date);
    }
}
