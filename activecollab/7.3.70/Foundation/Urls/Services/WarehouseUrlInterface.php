<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\Services;

use ActiveCollab\Foundation\Urls\ExternalUrlInterface;

interface WarehouseUrlInterface extends ExternalUrlInterface
{
    const WAREHOUSE_DOMAIN = 'https://warehouse.activecollab.com';

    const FILE_ACTION_DOWNLOAD = 'download';
    const FILE_ACTION_PREVIEW = 'preview';
    const FILE_ACTION_THUMBNAILS = 'thumbnails';

    const FILE_ACTIONS = [
        self::FILE_ACTION_DOWNLOAD,
        self::FILE_ACTION_PREVIEW,
        self::FILE_ACTION_THUMBNAILS,
    ];

    const FILE_EXTENSION_DOWNLOAD = '--DOWNLOAD-TOKEN--';
    const FILE_EXTENSION_PREVIEW = '--PREVIEW-TOKEN--';
    const FILE_EXTENSION_THUMBNAILS = '--THUMBNAIL-TOKEN--';

    const FILE_ACTION_EXTENSIONS = [
        self::FILE_ACTION_DOWNLOAD => self::FILE_EXTENSION_DOWNLOAD,
        self::FILE_ACTION_PREVIEW => self::FILE_EXTENSION_PREVIEW,
        self::FILE_ACTION_THUMBNAILS => self::FILE_EXTENSION_THUMBNAILS,
    ];

    public function isFile(): bool;
    public function getFileLocation(): ?string;
    public function getFileMd5Hash(): ?string;
    public function getFileAction(): ?string;
}
