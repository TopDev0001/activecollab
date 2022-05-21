<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Storage\Capacity;

class StorageStorageCapacityCalculator implements StorageCapacityCalculatorInterface
{
    private $capacity;
    private $grace_percent;
    private $grace_bytes_up_to;

    public function __construct(int $capacity, int $grace_percent = 0, $grace_bytes_up_to = 0)
    {
        $this->capacity = $capacity;
        $this->grace_percent = $grace_percent;
        $this->grace_bytes_up_to = $grace_bytes_up_to;
    }

    public function getCapacity(bool $be_graceful = false): int
    {
        $max_allowed_capacity = $this->capacity;

        if ($be_graceful) {
            $max_allowed_capacity += $this->getGraceAllowance();
        }

        return $max_allowed_capacity;
    }

    public function isCapacityReached(int $current_usage, bool $be_graceful = false): bool
    {
        if (empty($this->capacity)) {
            return false;
        }

        return $this->getCapacity($be_graceful) <= $current_usage;
    }

    public function getGraceAllowance(): int
    {
        if ($this->grace_bytes_up_to && $this->grace_bytes_up_to) {
            return (int) min(
                floor($this->grace_percent * $this->capacity / 100),
                $this->grace_bytes_up_to
            );
        }

        return 0;
    }
}
