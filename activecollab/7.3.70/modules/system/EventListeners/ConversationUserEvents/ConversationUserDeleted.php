<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\EventListeners\ConversationUserEvents;

use ActiveCollab\EventsDispatcher\Events\EventInterface;
use ActiveCollab\Logger\LoggerInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ConversationUserEvents\ConversationUserDeletedEventInterface;
use ActiveCollab\Module\System\Model\Conversation\ConversationInterface;
use ActiveCollab\Module\System\Model\Conversation\GroupConversationInterface;
use ConversationUser;

class ConversationUserDeleted implements EventInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(ConversationUserDeletedEventInterface $event)
    {
        /** @var ConversationUser $conversation_user */
        $conversation_user = $event->getObject();

        $conversation = $conversation_user->getConversation();

        if ($this->shouldDeleteConversation($conversation)) {
            $conversation->delete();

            $this->logger->info(
                'Group conversation deleted after last member leave it.',
                [
                    'conversation' => $conversation->getId(),
                ]
            );
        } else {
            $conversation->touch();
        }
    }

    private function shouldDeleteConversation(ConversationInterface $conversation): bool
    {
        return $conversation instanceof GroupConversationInterface && count($conversation->getMemberIds(false)) < 2;
    }
}
