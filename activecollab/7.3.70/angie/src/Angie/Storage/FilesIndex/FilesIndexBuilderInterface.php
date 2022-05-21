<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Storage\FilesIndex;

interface FilesIndexBuilderInterface
{
    public function getFilesIndex(array $types, bool $include_trashed_items = false): array;
}
