<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Storage\Usage;

use DateValue;

interface StorageUsageSnapshotInterface
{
    const NUMBER_OF_FILES_DATA_KEY = 'number_of_files';
    const TOTAL_FILE_SIZE_DATA_KEY = 'total_file_size';

    public function getDay(): DateValue;

    public function getUsage(): int;

    public function getUsageByService(string $storage_service): int;

    public function getUsageOfOurStorage(): int;

    public function getNumberOfFiles(): int;

    public function getNumberOfFilesByService(string $storage_service): int;
}
