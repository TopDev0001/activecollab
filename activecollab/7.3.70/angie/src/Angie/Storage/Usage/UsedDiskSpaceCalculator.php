<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Storage\Usage;

use ActiveCollab\CurrentTimestamp\CurrentTimestampInterface;
use Angie\Storage\ServicesManager\StorageServicesManagerInterface;
use DateValue;

class UsedDiskSpaceCalculator implements UsedDiskSpaceCalculatorInterface
{
    private $storage_services_manager;
    private $current_timestamp;

    public function __construct(
        StorageServicesManagerInterface $storage_services_manager,
        CurrentTimestampInterface $current_timestamp
    )
    {
        $this->storage_services_manager = $storage_services_manager;
        $this->current_timestamp = $current_timestamp;
    }

    private $snapshots = [];

    public function getUsageSnapshot(DateValue $day = null, bool $reload = false): StorageUsageSnapshotInterface
    {
        if (empty($day)) {
            $day = DateValue::makeFromTimestamp($this->current_timestamp->getCurrentTimestamp());
        }

        $key = $day->format('Y-m-d');

        if (empty($this->snapshots[$key]) || $reload) {
            $this->snapshots[$key] = (new StorageUsageSnapshotFactory($this->storage_services_manager))->getSnapshotForDay($day);
        }

        return $this->snapshots[$key];
    }

    public function getDiskUsage(DateValue $day = null, bool $reload = false): int
    {
        return $this->getUsageSnapshot($day, $reload)->getUsageOfOurStorage();
    }
}
