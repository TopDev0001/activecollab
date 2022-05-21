<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Files\Utils\FileDuplicator;

use IFile;

interface FileDuplicatorInterface
{
    public function duplicate(IFile $file, string $file_type = null): ?string;
}
