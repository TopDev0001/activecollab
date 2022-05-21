<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Storage;

use ActiveCollab\Logger\LoggerInterface;
use Angie\Storage\StorageAdapterInterface;
use Angie\Storage\ServicesManager\StorageServicesManagerInterface;
use Angie\Storage\ServicesManager\StorageStorageServicesManager;
use AngieApplication;

abstract class StorageAdapter implements StorageAdapterInterface
{
    protected $storage_services_manager;
    protected $logger;

    public function __construct(
        StorageStorageServicesManager $storage_services_manager,
        LoggerInterface $logger
    )
    {
        $this->storage_services_manager = $storage_services_manager;
        $this->logger = $logger;
    }

    public function deleteFile(string $file_type, ?string $location): void
    {
        $service_type = $this->storage_services_manager->getServiceTypeFromFileType($file_type);

        if ($service_type === StorageServicesManagerInterface::SERVICE_LOCAL) {
            $file_path = AngieApplication::fileLocationToPath($location);

            if (is_file($file_path)) {
                @unlink($file_path);
            } else {
                $this->logger->warning(
                    'Attempted to delete non existing local file with location: {location}',
                    [
                        'location' => $location,
                    ]
                );
            }
        }
    }
}
