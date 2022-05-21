<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\EventListeners\TeamEvents;

use ActiveCollab\EventsDispatcher\Events\EventInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\TeamEvents\TeamUpdatedEventInterface;
use ActiveCollab\Module\System\Utils\Conversations\ConversationResolverInterface;
use Team;

class TeamUpdated implements EventInterface
{
    private ConversationResolverInterface $conversation_resolver;
    private $conversation_updater;

    public function __construct(
        ConversationResolverInterface $conversation_resolver,
        callable $conversation_updater
    ) {
        $this->conversation_resolver = $conversation_resolver;
        $this->conversation_updater = $conversation_updater;
    }

    public function __invoke(TeamUpdatedEventInterface $event)
    {
        /** @var Team $team */
        $team = $event->getObject();

        if ($conversation = $this->conversation_resolver->getParentObjectConversation($team)) {
            call_user_func(
                $this->conversation_updater,
                $conversation,
                [
                    'name' => $team->getName(),
                ]
            );
        }
    }
}
