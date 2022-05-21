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
use ActiveCollab\Module\System\Model\Message\UserRenamedConversationMessage;
use ActiveCollab\Module\System\Utils\Conversations\ConversationMessageFactoryInterface;
use User;

class GroupConversationRenameService extends ConversationService implements GroupConversationRenameServiceInterface
{
    private ConversationMessageFactoryInterface $message_factory;

    public function __construct(
        EventsDispatcherInterface $events_dispatcher,
        ConversationMessageFactoryInterface $message_factory
    )
    {
        parent::__construct($events_dispatcher);

        $this->message_factory = $message_factory;
    }

    public function rename(
        GroupConversationInterface $conversation,
        User $user,
        string $name
    ): GroupConversationInterface
    {
        $this->checkCanManage($conversation, $user);

        $system_message_data = [
            'from_name' => $conversation->getName(),
            'to_name' => $name,
        ];

        $conversation->setName($name ?: null);
        $conversation->save();

        $this->message_factory->createSystemMessage(
            UserRenamedConversationMessage::class,
            $conversation,
            $user,
            $system_message_data
        );

        $this->events_dispatcher->trigger(new ConversationUpdatedEvent($conversation));

        return $conversation;
    }
}
