<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Storage\ServicesManager;

use InvalidArgumentException;

class StorageStorageServicesManager implements StorageServicesManagerInterface
{
    public function getServices(): array
    {
        return StorageServicesManagerInterface::SERVICES;
    }

    public function getServiceName(string $service): string
    {
        switch ($service) {
            case StorageServicesManagerInterface::SERVICE_LOCAL:
                return 'Local Files';
            case StorageServicesManagerInterface::SERVICE_WAREHOUSE:
                return 'Warehouse';
            case StorageServicesManagerInterface::SERVICE_GOOGLE_DRIVE:
                return 'Google Drive';
            case StorageServicesManagerInterface::SERVICE_DROPBOX:
                return 'Dropbox';
            default:
                throw new InvalidArgumentException("Invalid storage service '{$service}'");
        }
    }

    public function getServiceTypeFromFileType(string $file_type): string
    {
        if (in_array($file_type, StorageServicesManagerInterface::DROPBOX_FILE_TYPES)) {
            return StorageServicesManagerInterface::SERVICE_DROPBOX;
        } elseif (in_array($file_type, StorageServicesManagerInterface::GOOGLE_DRIVE_FILE_TYPES)) {
            return StorageServicesManagerInterface::SERVICE_GOOGLE_DRIVE;
        } elseif (in_array($file_type, StorageServicesManagerInterface::WAREHOUSE_FILE_TYPES)) {
            return StorageServicesManagerInterface::SERVICE_WAREHOUSE;
        } elseif (in_array($file_type, StorageServicesManagerInterface::LOCAL_FILE_TYPES)) {
            return StorageServicesManagerInterface::SERVICE_LOCAL;
        } else {
            throw new InvalidArgumentException("Unknown file type '{$file_type}'.");
        }
    }
}
