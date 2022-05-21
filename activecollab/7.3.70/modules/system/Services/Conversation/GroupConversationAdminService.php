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
use ActiveCollab\Module\System\Utils\Conversations\GroupConversationAdminGeneratorInterface;
use User;

class GroupConversationAdminService extends ConversationService implements GroupConversationAdminServiceInterface
{
    private GroupConversationAdminGeneratorInterface $admin_generator;

    public function __construct(
        EventsDispatcherInterface $events_dispatcher,
        GroupConversationAdminGeneratorInterface $admin_generator
    )
    {
        parent::__construct($events_dispatcher);

        $this->admin_generator = $admin_generator;
    }

    public function promoteToAdmin(
        GroupConversationInterface $conversation,
        User $user,
        User $by
    ): GroupConversationInterface
    {
        $conversation = $this->setConversationAdmin($conversation, $user, $by, true);

        $this->events_dispatcher->trigger(new ConversationUpdatedEvent($conversation));

        return $conversation;
    }

    public function revokeAsAdmin(
        GroupConversationInterface $conversation,
        User $user,
        User $by
    ): GroupConversationInterface
    {
        $conversation = $this->setConversationAdmin($conversation, $user, $by, false);

        if ($this->shouldTryGenerateAdmin($user, $by)) {
            $conversation = $this->admin_generator->generate($conversation, [$user->getId()]);
        }

        $this->events_dispatcher->trigger(new ConversationUpdatedEvent($conversation));

        return $conversation;
    }

    private function shouldTryGenerateAdmin(User $user, User $by): bool
    {
        return $user->getId() === $by->getId() || $by->isOwner();
    }

    private function setConversationAdmin(
        GroupConversationInterface $conversation,
        User $user,
        User $by,
        bool $is_admin
    ): GroupConversationInterface
    {
        $this->checkCanManage($conversation, $by);
        $this->checkIsMember($conversation, $user);

        $conversation_user = $conversation->getConversationUser($user);
        $conversation_user->setIsAdmin($is_admin);
        $conversation_user->save();

        $conversation->touch();

        return $conversation;
    }
}
