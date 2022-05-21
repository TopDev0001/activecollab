<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Utils;

use ActiveCollab\CurrentTimestamp\CurrentTimestampInterface;
use DateTimeValue;

class CurrentTimestamp implements CurrentTimestampInterface
{
    public function getCurrentTimestamp(): int
    {
        return DateTimeValue::getCurrentTimestamp();
    }
}
