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

class CollectionResult extends Result
{
    public function __construct(string $name, array $value, DateValue $date)
    {
        if (!is_array($value) || empty($value)) {
            throw new InvalidArgumentException('Collection value must be a not-empty array.');
        }

        parent::__construct($name, $value, $date);
    }
}
