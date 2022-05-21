<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\SkipWorkingDaysResolver;

use ConfigOptions;

class SkipWorkingDaysResolver
{
    public function __invoke(): bool
    {
        return (bool) ConfigOptions::getValue('skip_days_off_when_rescheduling');
    }
}
