<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Services\Conversation;

use ActiveCollab\EventsDispatcher\EventsDispatcherInterface;
use ActiveCollab\Module\System\Model\Conversation\ConversationInterface;
use ActiveCollab\Module\System\Model\Conversation\GroupConversationInterface;
use RuntimeException;
use User;

abstract class ConversationService implements ConversationServiceInterface
{
    protected EventsDispatcherInterface $events_dispatcher;

    public function __construct(EventsDispatcherInterface $events_dispatcher)
    {
        $this->events_dispatcher = $events_dispatcher;
    }

    protected function checkIsMember(ConversationInterface $conversation, User $user): void
    {
        if (!$conversation->isMember($user)) {
            throw new RuntimeException("User isn't participant of this conversation.");
        }
    }

    protected function checkCanManage(GroupConversationInterface $conversation, User $user): void
    {
        if (!$conversation->canManage($user)) {
            throw new RuntimeException(
                sprintf(
                    'User #%s does not have permission to manage this conversation.',
                    $user->getId()
                )
            );
        }
    }
}
