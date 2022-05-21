<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Mail\Incoming\Processor\MailboxesSet;

class MailboxesSet implements MailboxesSetInterface
{
    private array $mailboxes;

    public function __construct(string ...$mailboxes)
    {
        $this->mailboxes = $mailboxes;
    }

    public function getMailboxes(): array
    {
        return $this->mailboxes;
    }
}
