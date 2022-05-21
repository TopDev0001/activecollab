<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Metric;

use Angie\Metric\Result\FlagResult;

abstract class Flag extends Metric implements FlagInterface
{
    protected function getClassNameSufix(): string
    {
        return 'Flag';
    }

    protected function getResultClassName(): string
    {
        return FlagResult::class;
    }
}
