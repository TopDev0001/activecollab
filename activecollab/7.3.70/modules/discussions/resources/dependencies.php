<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\Discussions\Utils\DiscussionToTaskConverter\DiscussionToTaskConverter;
use ActiveCollab\Module\Discussions\Utils\DiscussionToTaskConverter\DiscussionToTaskConverterInterface;
use function DI\get;

return [
    DiscussionToTaskConverterInterface::class => get(DiscussionToTaskConverter::class),
];
