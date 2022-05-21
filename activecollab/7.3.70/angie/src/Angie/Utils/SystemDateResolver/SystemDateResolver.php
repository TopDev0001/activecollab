<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Utils\SystemDateResolver;

use DateTimeValue;
use DateValue;

class SystemDateResolver implements SystemDateResolverInterface
{
    public function getSystemDate(): DateValue
    {
        return DateTimeValue::now()->getSystemDate();
    }
}
