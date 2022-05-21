<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Storage\ServicesManager;

use DropboxAttachment;
use DropboxFile;
use DropboxUploadedFile;
use GoogleDriveAttachment;
use GoogleDriveFile;
use GoogleDriveUploadedFile;
use LocalAttachment;
use LocalFile;
use LocalUploadedFile;
use WarehouseAttachment;
use WarehouseFile;
use WarehouseUploadedFile;

interface StorageServicesManagerInterface
{
    public const WAREHOUSE_FILE_TYPES = [
        WarehouseFile::class,
        WarehouseAttachment::class,
        WarehouseUploadedFile::class,
    ];

    public const SERVICES = [
        StorageServicesManagerInterface::SERVICE_LOCAL,
        StorageServicesManagerInterface::SERVICE_WAREHOUSE,
        StorageServicesManagerInterface::SERVICE_GOOGLE_DRIVE,
        StorageServicesManagerInterface::SERVICE_DROPBOX,
    ];

    public const DROPBOX_FILE_TYPES = [
        DropboxFile::class,
        DropboxAttachment::class,
        DropboxUploadedFile::class,
    ];

    public const SERVICE_DROPBOX = 'dropbox';
    public const GOOGLE_DRIVE_FILE_TYPES = [
        GoogleDriveFile::class,
        GoogleDriveAttachment::class,
        GoogleDriveUploadedFile::class,
    ];

    public const SERVICE_LOCAL = 'local';
    public const SERVICE_WAREHOUSE = 'warehouse';
    public const SERVICE_GOOGLE_DRIVE = 'google_drive';
    public const LOCAL_FILE_TYPES = [
        LocalFile::class,
        LocalAttachment::class,
        LocalUploadedFile::class,
    ];

    public function getServices(): array;
    public function getServiceName(string $service): string;
    public function getServiceTypeFromFileType(string $file_type): string;
}
