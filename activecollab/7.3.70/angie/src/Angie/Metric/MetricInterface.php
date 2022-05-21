<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Metric;

use Angie\Metric\Result\ResultInterface;
use DateValue;

interface MetricInterface
{
    public function getName(): string;
    public function getValueFor(DateValue $date): ResultInterface;
}
