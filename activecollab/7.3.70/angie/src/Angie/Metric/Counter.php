<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Metric;

use Angie\Metric\Result\CounterResult;

abstract class Counter extends Metric implements CounterInterface
{
    protected function getClassNameSufix(): string
    {
        return 'Counter';
    }

    protected function getResultClassName(): string
    {
        return CounterResult::class;
    }
}
