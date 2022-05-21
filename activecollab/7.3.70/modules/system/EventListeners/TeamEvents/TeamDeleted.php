<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\EventListeners\TeamEvents;

use ActiveCollab\EventsDispatcher\Events\EventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\TeamEvents\TeamDeletedEventInterface;
use ActiveCollab\Module\System\Utils\Conversations\ConversationResolverInterface;
use ActiveCollab\Module\System\Utils\Conversations\ParentObjectToGroupConversationConverterInterface;
use Team;

class TeamDeleted implements EventInterface
{
    private ConversationResolverInterface $conversation_resolver;
    private ParentObjectToGroupConversationConverterInterface $conversation_converter;

    public function __construct(
        ConversationResolverInterface $conversation_resolver,
        ParentObjectToGroupConversationConverterInterface $conversation_converter
    ) {
        $this->conversation_resolver = $conversation_resolver;
        $this->conversation_converter = $conversation_converter;
    }

    public function __invoke(TeamDeletedEventInterface $event)
    {
        /** @var Team $team */
        $team = $event->getObject();

        if ($conversation = $this->conversation_resolver->getParentObjectConversation($team)) {
            $conversation->hasMessages()
                ? $this->conversation_converter->convert($conversation)
                : $conversation->delete();
        }
    }
}
