<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\EventListeners\UserEvents;

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\UserEvents\UserMovedToArchiveEventInterface;

class UserMovedToArchive extends UserMovedTo
{
    public function __invoke(UserMovedToArchiveEventInterface $event)
    {
        $user = $event->getObject();

        $this->reassignUserConversationsAdmins($user);
    }
}
