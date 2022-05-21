<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\User;

use ActiveCollab\EventsDispatcher\Events\EventInterface;
use DateTimeValue;
use User;

interface SessionStartedEventInterface extends EventInterface
{
    public function getLastLogInBeforeStartSession(): ?DateTimeValue;

    public function getUser(): User;
}
