<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\EventListeners\MessageEvents;

use ActiveCollab\EventsDispatcher\Events\EventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\MessageEvents\MessageUpdatedEventInterface;
use ActiveCollab\Module\System\Utils\Conversations\MessageMentionResolverInterface;
use Conversation;
use Message;

class MessageUpdated implements EventInterface
{
    private MessageMentionResolverInterface $mention_resolver;

    public function __construct(MessageMentionResolverInterface $mention_resolver)
    {
        $this->mention_resolver = $mention_resolver;
    }

    public function __invoke(MessageUpdatedEventInterface $event)
    {
        /** @var Message $message */
        $message = $event->getObject();

        /** @var Conversation $conversation */
        $conversation = $message->getConversation();

        if ($message->getNewMentions()) {
            $this->mention_resolver->resolve($message);
        }

        $conversation->touch();
    }
}
