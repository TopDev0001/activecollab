<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\EventListeners\UserEvents;

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\TrashEvents\MovedToTrashEventInterface;
use User;

class UserMovedToTrash extends UserMovedTo
{
    public function __invoke(MovedToTrashEventInterface $event)
    {
        $object = $event->getObject();

        if ($object instanceof User) {
            $this->reassignUserConversationsAdmins($object);
        }
    }
}
