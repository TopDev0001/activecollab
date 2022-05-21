<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

interface EmailMessageInterface
{
    public function getHeaders(): array;
    public function getSenders(): array;
    public function getRecipients(): array;
    public function getReferences(): array;
    public function getSubject(): string;
    public function getBody(): string;
    public function getAttachments(): array;
    public function isForwardingNotification(): bool;
    public function isFromAutoResponder(): bool;
}
