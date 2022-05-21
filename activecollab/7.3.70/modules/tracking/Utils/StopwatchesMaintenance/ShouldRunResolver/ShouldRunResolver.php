<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Utils\StopwatchesMaintenance\ShouldRunResolver;

class ShouldRunResolver implements ShouldRunResolverInterface
{
    public function shouldRun(
        array $stopwatches_for_daily,
        array $stopwatches_for_maximum
    ): bool
    {
        return !empty($stopwatches_for_daily) || !empty($stopwatches_for_maximum);
    }
}
