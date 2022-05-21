<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Metric;

use Angie\Metric\Result\TimerResult;

abstract class Timer extends Metric implements TimerInterface
{
    protected function getClassNameSufix(): string
    {
        return 'Timer';
    }

    protected function getResultClassName(): string
    {
        return TimerResult::class;
    }
}
