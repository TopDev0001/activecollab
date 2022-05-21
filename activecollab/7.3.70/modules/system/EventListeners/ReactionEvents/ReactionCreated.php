<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\EventListeners\ReactionEvents;

use ActiveCollab\EventsDispatcher\Events\EventInterface;
use ActiveCollab\Logger\LoggerInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ReactionEvents\ReactionCreatedEventInterface;
use ActiveCollab\Module\System\Model\Message\MessageInterface;
use Angie\Notifications\NotificationsInterface;
use Comment;
use Reaction;

class ReactionCreated implements EventInterface
{
    private NotificationsInterface $notifications;
    private LoggerInterface $logger;

    public function __construct(
        NotificationsInterface $notifications,
        LoggerInterface $logger
    )
    {
        $this->notifications = $notifications;
        $this->logger = $logger;
    }

    public function __invoke(ReactionCreatedEventInterface $event)
    {
        /** @var Reaction $reaction */
        $reaction = $event->getObject();

        $parent = $reaction->getParent();

        $parent->touch();

        if ($parent instanceof Comment) {
            $this->notifications
                ->notifyAbout('new_reaction', $parent->getParent(), $reaction->getCreatedBy())
                ->setComment($parent)
                ->setReaction($reaction)
                ->sendToUsers([$parent->getCreatedBy()]);
        }

        if ($parent instanceof MessageInterface) {
            $parent->getConversation()->touch();
        }

        $this->logger->info(
            'Reaction added: {reaction_type}',
            [
                'reaction_type' => $reaction->getType(),
            ]
        );
    }
}
