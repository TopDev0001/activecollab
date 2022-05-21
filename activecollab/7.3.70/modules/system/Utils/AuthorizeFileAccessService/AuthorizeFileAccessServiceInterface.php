<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Utils\AuthorizeFileAccessService;

use IFile;
use User;

interface AuthorizeFileAccessServiceInterface
{
    public function authorize(
        IFile $file,
        string $intent,
        User $user,
        bool $force = false,
        ?int $width = null,
        ?int $height = null,
        ?string $scale = null
    ): string;
}
