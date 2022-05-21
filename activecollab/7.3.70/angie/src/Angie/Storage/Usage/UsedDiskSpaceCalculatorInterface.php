<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Storage\Usage;

use DateValue;

interface UsedDiskSpaceCalculatorInterface
{
    public function getUsageSnapshot(DateValue $day = null, bool $reload = false): StorageUsageSnapshotInterface;
    public function getDiskUsage(DateValue $day = null, bool $reload = false): int;
}
