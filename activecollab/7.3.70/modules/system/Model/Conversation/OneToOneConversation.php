<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Model\Conversation;

use ActiveCollab\Module\System\Utils\UsersDisplayNameResolver\UsersDisplayNameResolverInterface;
use AngieApplication;
use IUser;

class OneToOneConversation extends CustomConversation
{
    public function getDisplayName(IUser $user): string
    {
        return (string) AngieApplication::getContainer()
            ->get(UsersDisplayNameResolverInterface::class)
            ->getNameFor($this, $user, 'Group');
    }
}
