<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Authentication\SecurityLog\EventHandlers;

use ActiveCollab\Authentication\AuthenticatedUser\AuthenticatedUserInterface;
use Angie\Globalization;
use User;

class UserSetEventHander
{
    public function __invoke(?AuthenticatedUserInterface $user): void
    {
        if ($user instanceof User) {
            Globalization::setCurrentLocaleByUser($user);
        }
    }
}
