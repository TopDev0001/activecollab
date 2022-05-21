<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Mail\Incoming\Message;

interface MessageInterface
{
    public function getSender(): string;
    public function getRecipients(): array;
    public function getSubject(): string;
    public function getBody(): string;
    public function getAttachments(): array;
    public function getReferences(): array;
    public function getTrimmedReferences(): array;
    public function getMailer(): string;
}
