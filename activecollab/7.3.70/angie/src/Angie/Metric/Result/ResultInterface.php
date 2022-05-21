<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Metric\Result;

use DateValue;

interface ResultInterface
{
    public function getName(): string;
    public function getValue();
    public function getDate(): DateValue;
    public function addTo(array &$stats): void;
}
