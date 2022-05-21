<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Mailer\Adapter;

use DataObject;
use IUser;

final class Silent extends Adapter
{
    public function send(
        IUser $sender,
        IUser $recipient,
        string $subject,
        string $body,
        DataObject $context = null,
        iterable $attachments = null,
        callable $on_sent = null
    ): int
    {
        return $this->messageSent(
            $sender,
            $recipient,
            $subject,
            $body,
            $context,
            $attachments,
            $on_sent
        );
    }
}
