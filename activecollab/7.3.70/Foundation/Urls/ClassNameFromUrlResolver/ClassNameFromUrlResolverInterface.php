<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\ClassNameFromUrlResolver;

use ActiveCollab\Module\System\Model\Message\UserMessage;
use Comment;
use Project;
use Task;

interface ClassNameFromUrlResolverInterface
{
    const SHORT_CLASS_NAMES_MAP = [
        'project' => Project::class,
        'task' => Task::class,
        'comment' => Comment::class,
        'user-message' => UserMessage::class,
    ];

    public function getClassNameFromUrl(string $url_parent_type, string $must_implement = null): string;
}
