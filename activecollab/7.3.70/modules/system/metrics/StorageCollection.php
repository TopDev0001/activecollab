<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Metric;

use Angie\Metric\Collection;
use Angie\Metric\Result\ResultInterface;
use Angie\Storage\Capacity\StorageCapacityCalculatorInterface;
use Angie\Storage\ServicesManager\StorageServicesManagerInterface;
use Angie\Storage\Usage\StorageUsageSnapshotFactory;
use Angie\Storage\Usage\StorageUsageSnapshotInterface;
use DateValue;

class StorageCollection extends Collection
{
    private $storage_services_manager;
    private $capacity_calculator;

    public function __construct(
        StorageServicesManagerInterface $storage_services_manager,
        StorageCapacityCalculatorInterface $capacity_calculator
    )
    {
        $this->storage_services_manager = $storage_services_manager;
        $this->capacity_calculator = $capacity_calculator;
    }

    public function getValueFor(DateValue $date): ResultInterface
    {
        $snapshot = (new StorageUsageSnapshotFactory($this->storage_services_manager))->getSnapshotForDay($date);

        $storage_usage = $snapshot->getUsage();

        return $this->produceResult(
            [
                'number_of_files' => $snapshot->getNumberOfFiles(),
                'number_of_files_by_service' => $this->getNumberOfFilesByService($snapshot),
                'our_storage_used' => $snapshot->getUsageOfOurStorage(),
                'storage_used' => $storage_usage,
                'storage_used_by_service' => $this->getUsageByService($snapshot),
                'is_disk_full' => $this->capacity_calculator->isCapacityReached($storage_usage),
                'is_disk_full_graceful' => $this->capacity_calculator->isCapacityReached($storage_usage, true),
            ],
            $date
        );
    }

    private function getNumberOfFilesByService(StorageUsageSnapshotInterface $snapshot): array
    {
        $result = [];

        foreach (StorageServicesManagerInterface::SERVICES as $service) {
            $result[$service] = $snapshot->getNumberOfFilesByService($service);
        }

        return $result;
    }

    private function getUsageByService(StorageUsageSnapshotInterface $snapshot): array
    {
        $result = [];

        foreach (StorageServicesManagerInterface::SERVICES as $service) {
            $result[$service] = $snapshot->getUsageByService($service);
        }

        return $result;
    }
}
