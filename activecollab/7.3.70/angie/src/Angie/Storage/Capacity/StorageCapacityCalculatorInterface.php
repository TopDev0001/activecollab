<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Storage\Capacity;

interface StorageCapacityCalculatorInterface
{
    public function getCapacity(bool $be_graceful = false): int;
    public function isCapacityReached(int $current_usage, bool $be_graceful = false): bool;
    public function getGraceAllowance(): int;
}
