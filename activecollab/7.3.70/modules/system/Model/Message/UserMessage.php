<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Model\Message;

use ActiveCollab\Module\System\Services\Message\UserMessage\MarkAsUnreadServiceInterface;
use AngieApplication;
use IAttachments;
use IAttachmentsImplementation;
use IReactions;
use IReactionsImplementation;
use LogicException;
use Message;
use Messages;
use User;

class UserMessage extends Message implements UserMessageInterface, IAttachments, IReactions
{
    use IAttachmentsImplementation;
    use IReactionsImplementation;

    public function update(
        string $body,
        array $attach_uploaded_files = [],
        array $drop_attached_files = []
    ): UserMessageInterface
    {
        if (!$this->countAttachments() && empty($body) && empty($attach_uploaded_files)) {
            throw new LogicException('Message should has at least body or attachment.');
        }

        return Messages::update(
            $this,
            [
                'body' => $body ?: null,
                'attach_uploaded_files' => $attach_uploaded_files,
                'drop_attached_files' => $drop_attached_files,
            ]
        );
    }

    public function markAsUnreadFor(User $user): void
    {
        AngieApplication::getContainer()
            ->get(MarkAsUnreadServiceInterface::class)
            ->markAsUnread($this, $user);
    }
}
