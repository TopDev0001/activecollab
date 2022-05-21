<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Model\Conversation;

use ActiveCollab\Module\System\Model\Message\UserMessageInterface;
use ConversationUser;
use CursorModelCollection;
use DateTimeValue;
use IUser;
use ModelCollection;
use User;

interface ConversationInterface
{
    const CONVERSATION_TYPE_CUSTOM = 'custom';
    const CONVERSATION_TYPE_GROUP = 'group';

    const CONVERSATION_TYPES = [
        self::CONVERSATION_TYPE_CUSTOM,
        self::CONVERSATION_TYPE_GROUP,
    ];

    public function createMessage(
        User $user,
        string $body,
        array $attachments = [],
        ?int $order_id = null
    ): UserMessageInterface;

    public function getMessages(User $user): ModelCollection;

    public function getAttachments(User $user): CursorModelCollection;

    public function newMessagesSince(User $user, DateTimeValue $time = null): ConversationUser;

    public function getConversationUser(User $user): ?ConversationUser;

    public function hasMessages(): bool;

    public function getDisplayName(IUser $user): string;
}
