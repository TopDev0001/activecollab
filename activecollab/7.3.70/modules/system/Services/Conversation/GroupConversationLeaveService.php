<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Services\Conversation;

use ActiveCollab\EventsDispatcher\EventsDispatcherInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ConversationEvents\ConversationUpdatedEvent;
use ActiveCollab\Module\System\Model\Conversation\GroupConversationInterface;
use ActiveCollab\Module\System\Model\Message\UserLeftConversationMessage;
use ActiveCollab\Module\System\Utils\Conversations\ConversationMessageFactoryInterface;
use ActiveCollab\Module\System\Utils\Conversations\GroupConversationAdminGeneratorInterface;
use User;

class GroupConversationLeaveService extends ConversationService implements GroupConversationLeaveServiceInterface
{
    private GroupConversationAdminGeneratorInterface $admin_generator;
    private ConversationMessageFactoryInterface $message_factory;

    public function __construct(
        EventsDispatcherInterface $events_dispatcher,
        GroupConversationAdminGeneratorInterface $admin_generator,
        ConversationMessageFactoryInterface $message_factory
    )
    {
        parent::__construct($events_dispatcher);

        $this->admin_generator = $admin_generator;
        $this->message_factory = $message_factory;
    }

    public function leave(GroupConversationInterface $conversation, User $user): void
    {
        $this->checkIsMember($conversation, $user);

        $is_admin = $conversation->isAdmin($user);

        $conversation->getConversationUser($user)->delete();

        if ($conversation->isLoaded()) {
            $this->message_factory->createSystemMessage(UserLeftConversationMessage::class, $conversation, $user);

            if ($is_admin) {
                $this->events_dispatcher->trigger(
                    new ConversationUpdatedEvent(
                        $this->admin_generator->generate(
                            $conversation,
                            [$user->getId()]
                        )
                    )
                );
            }
        }
    }
}
