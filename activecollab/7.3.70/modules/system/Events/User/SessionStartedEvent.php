<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\User;

use ActiveCollab\EventsDispatcher\Events\Event;
use DateTimeValue;
use User;

class SessionStartedEvent extends Event implements SessionStartedEventInterface
{
    private $last_login_on_before_start;
    private $user;

    public function __construct(
        User $object,
        ?DateTimeValue $last_login_on_before_start = null
    ) {
        $this->last_login_on_before_start = $last_login_on_before_start;
        $this->user = $object;
    }

    public function getLastLogInBeforeStartSession(): ?DateTimeValue
    {
        return $this->last_login_on_before_start;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
