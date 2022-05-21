<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Storage\Usage;

use Angie\Storage\ServicesManager\StorageServicesManagerInterface;
use DateValue;
use InvalidArgumentException;

class StorageUsageSnapshot implements StorageUsageSnapshotInterface
{
    private $day;
    private $total_number_of_files = 0;
    private $total_number_of_attachments = 0;
    private $total_number_of_uploaded_files = 0;
    private $usage_data_by_service = [];
    private $total_number_of_items = 0;
    private $usage = 0;

    public function __construct(
        DateValue $day,
        int $total_number_of_files,
        int $total_number_of_attachments,
        int $total_number_of_uploaded_files,
        array $usage_data_by_service
    )
    {
        $this->day = $day;
        $this->total_number_of_files = $total_number_of_files;
        $this->total_number_of_attachments = $total_number_of_attachments;
        $this->total_number_of_uploaded_files = $total_number_of_uploaded_files;

        foreach ($usage_data_by_service as $service => $usage_data) {
            if (!in_array($service, StorageServicesManagerInterface::SERVICES)) {
                throw new InvalidArgumentException("Storage service '{$service}' is not known.");
            }

            $this->validateUsageData($usage_data);

            $this->total_number_of_items += (int) $usage_data[self::NUMBER_OF_FILES_DATA_KEY];
            $this->usage += (int) $usage_data[self::TOTAL_FILE_SIZE_DATA_KEY];
        }

        $this->usage_data_by_service = $usage_data_by_service;
    }

    private function validateUsageData($usage_data): void
    {
        if (!is_array($usage_data)) {
            throw new InvalidArgumentException('Usage data should be an array');
        }

        if (count($usage_data) > 2) {
            throw new InvalidArgumentException('Usage data should have only two elements');
        }

        foreach ([self::NUMBER_OF_FILES_DATA_KEY, self::TOTAL_FILE_SIZE_DATA_KEY] as $key) {
            if (!array_key_exists($key, $usage_data)) {
                throw new InvalidArgumentException("Key '{$key}' expected in usage data");
            }
        }
    }

    public function getDay(): DateValue
    {
        return DateValue::now();
    }

    public function getUsage(): int
    {
        return $this->usage;
    }

    public function getUsageByService(string $storage_service): int
    {
        return (int) $this->usage_data_by_service[$storage_service][self::TOTAL_FILE_SIZE_DATA_KEY];
    }

    public function getUsageOfOurStorage(): int
    {
        return $this->getUsageByService(StorageServicesManagerInterface::SERVICE_LOCAL) +
            $this->getUsageByService(StorageServicesManagerInterface::SERVICE_WAREHOUSE);
    }

    public function getNumberOfFiles(): int
    {
        return $this->total_number_of_items;
    }

    public function getNumberOfFilesByService(string $storage_service): int
    {
        return (int) $this->usage_data_by_service[$storage_service][self::NUMBER_OF_FILES_DATA_KEY];
    }
}
