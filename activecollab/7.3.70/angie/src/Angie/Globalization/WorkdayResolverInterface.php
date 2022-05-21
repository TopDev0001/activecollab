<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Globalization;

use DateValue;

interface WorkdayResolverInterface
{
    public function getWorkdays(): array;
    public function isWorkday(DateValue $date): bool;
    public function isWeekend(DateValue $date): bool;
}
