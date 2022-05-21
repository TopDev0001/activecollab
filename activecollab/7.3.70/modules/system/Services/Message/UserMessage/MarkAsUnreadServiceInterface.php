<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Services\Message\UserMessage;

use ActiveCollab\Module\System\Model\Message\UserMessage;
use User;

interface MarkAsUnreadServiceInterface extends UserMessageServiceInterface
{
    public function markAsUnread(UserMessage $message, User $user): void;
}
