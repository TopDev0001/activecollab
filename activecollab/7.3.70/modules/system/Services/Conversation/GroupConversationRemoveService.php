<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Services\Conversation;

use ActiveCollab\EventsDispatcher\EventsDispatcherInterface;
use ActiveCollab\Module\System\Model\Conversation\GroupConversationInterface;
use ActiveCollab\Module\System\Model\Message\UserRemovedConversationMessage;
use ActiveCollab\Module\System\Utils\Conversations\ConversationMessageFactoryInterface;
use ActiveCollab\Module\System\Utils\Conversations\GroupConversationAdminGeneratorInterface;
use RuntimeException;
use User;

class GroupConversationRemoveService extends ConversationService implements GroupConversationRemoveServiceInterface
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

    public function remove(GroupConversationInterface $conversation, User $user, User $by): GroupConversationInterface
    {
        $this->checkCanManage($conversation, $by);
        $this->checkIsMember($conversation, $user);

        if ($user->getId() === $by->getId()) {
            throw new RuntimeException('User cannot remove himself from the conversation.');
        }

        $is_admin = $conversation->isAdmin($user);

        $conversation->getConversationUser($user)->delete();

        $this->message_factory->createSystemMessage(
            UserRemovedConversationMessage::class,
            $conversation,
            $user,
            [
                'removed_by_id' => $by->getId(),
            ]
        );

        if ($is_admin) {
            $conversation = $this->admin_generator->generate($conversation, [$user->getId()]);
        }

        return $conversation;
    }
}
