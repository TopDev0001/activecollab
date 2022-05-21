<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Services\Conversation;

use ActiveCollab\CurrentTimestamp\CurrentTimestampInterface;
use ActiveCollab\EventsDispatcher\EventsDispatcherInterface;
use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ConversationEvents\ConversationUpdatedEvent;
use ActiveCollab\Module\System\Model\Conversation\GroupConversationInterface;
use ActiveCollab\Module\System\Model\Message\UserInvitedConversationMessage;
use ActiveCollab\Module\System\Utils\Conversations\ConversationMessageFactoryInterface;
use DateTimeValue;
use RuntimeException;
use User;

class GroupConversationInviteService extends ConversationService implements GroupConversationInviteServiceInterface
{
    private $users_finder;
    private CurrentTimestampInterface $current_timestamp;
    private ConversationMessageFactoryInterface $message_factory;

    public function __construct(
        EventsDispatcherInterface $events_dispatcher,
        callable $users_finder,
        CurrentTimestampInterface $current_timestamp,
        ConversationMessageFactoryInterface $message_factory
    )
    {
        parent::__construct($events_dispatcher);

        $this->users_finder = $users_finder;
        $this->current_timestamp = $current_timestamp;
        $this->message_factory = $message_factory;
    }

    public function invite(
        GroupConversationInterface $conversation,
        User $user,
        array $user_ids
    ): GroupConversationInterface
    {
        $this->checkCanManage($conversation, $user);

        $users = call_user_func(
            $this->users_finder,
            array_diff(
                $user_ids,
                $conversation->getMemberIds()
            )
        );

        if (empty($users)) {
            throw new RuntimeException('There are no new users to add or they do not exist.');
        }

        foreach ($users as $invited_user) {
            $conversation->setConversationUser(
                $invited_user,
                [
                    'new_messages_since' => new DateTimeValue($this->current_timestamp->getCurrentTimestamp()),
                ]
            );

            $this->message_factory->createSystemMessage(
                UserInvitedConversationMessage::class,
                $conversation,
                $invited_user,
                [
                    'invited_by_id' => $user->getId(),
                ]
            );
        }

        $conversation->touch();

        $this->events_dispatcher->trigger(new ConversationUpdatedEvent($conversation));

        return $conversation;
    }
}
