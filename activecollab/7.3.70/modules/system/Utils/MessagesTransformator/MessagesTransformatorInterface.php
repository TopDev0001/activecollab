<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\MessagesTransformator;

use ActiveCollab\Module\System\Model\Message\UserMessage;
use ApplicationObject;
use IUser;
use Project;

interface MessagesTransformatorInterface
{
    const PARAGRAPH_SEPARATOR = PHP_EOL;

    public function transform(
        Project $project,
        IUser $user,
        string $transform_to_class,
        UserMessage ...$message
    ): ?ApplicationObject;
}
