<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Storage;

interface StorageAdapterInterface
{
    public function deleteFile(string $file_type, ?string $location): void;
}
