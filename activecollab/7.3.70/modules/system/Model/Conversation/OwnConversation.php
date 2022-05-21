<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Model\Conversation;

use IUser;

class OwnConversation extends CustomConversation
{
    public function getDisplayName(IUser $user): string
    {
        return $this->getCreatedByName();
    }
}
