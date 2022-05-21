<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Metric;

use Angie\Metric\Result\CollectionResult;

abstract class Collection extends Metric implements CollectionInterface
{
    protected function getClassNameSufix(): string
    {
        return 'Collection';
    }

    protected function getResultClassName(): string
    {
        return CollectionResult::class;
    }
}
