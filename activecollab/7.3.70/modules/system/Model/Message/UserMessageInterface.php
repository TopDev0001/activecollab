<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Model\Message;

use User;

interface UserMessageInterface extends MessageInterface
{
    public function update(
        string $body,
        array $attach_uploaded_files = [],
        array $drop_attached_files = []
    ): UserMessageInterface;

    public function markAsUnreadFor(User $user): void;
}
