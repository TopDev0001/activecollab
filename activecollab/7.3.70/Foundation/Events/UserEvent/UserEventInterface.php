<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Events\UserEvent;

use ActiveCollab\Foundation\Events\EventInterface;

interface UserEventInterface extends EventInterface
{
    const WEBHOOK_CONTEXT_BILLING = 'User';

    public function getPayloadVersion(): string;

    public function jsonSerialize(): array;
}
