<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Storage\OveruseResolver;

use Angie\Storage\Capacity\StorageCapacityCalculatorInterface;
use Angie\Storage\Usage\UsedDiskSpaceCalculatorInterface;

class StorageOveruseResolver implements StorageOveruseResolverInterface
{
    private $used_disk_space_calculator;
    private $storage_capacity_calcualtor;

    public function __construct(
        UsedDiskSpaceCalculatorInterface $used_disk_space_calculator,
        StorageCapacityCalculatorInterface $storage_capacity_calcualtor
    ) {
        $this->used_disk_space_calculator = $used_disk_space_calculator;
        $this->storage_capacity_calcualtor = $storage_capacity_calcualtor;
    }

    public function isDiskFull(bool $be_graceful = false): bool
    {
        return $this->storage_capacity_calcualtor->isCapacityReached(
            $this->used_disk_space_calculator->getDiskUsage(null, true),
            $be_graceful
        );
    }
}
