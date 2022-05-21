<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Discussions\Utils\DiscussionToTaskConverter;

use Discussion;
use Task;
use User;

interface DiscussionToTaskConverterInterface
{
    public function convertToTask(Discussion $discussion, User $by): Task;
}
