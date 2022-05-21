<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Model\Conversation;

use ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ConversationUserEvents\ConversationUserCreatedEvent;
use ActiveCollab\Module\System\Services\Conversation\GroupConversationAdminServiceInterface;
use ActiveCollab\Module\System\Services\Conversation\GroupConversationInviteServiceInterface;
use ActiveCollab\Module\System\Services\Conversation\GroupConversationLeaveServiceInterface;
use ActiveCollab\Module\System\Services\Conversation\GroupConversationRemoveServiceInterface;
use ActiveCollab\Module\System\Services\Conversation\GroupConversationRenameServiceInterface;
use ActiveCollab\Module\System\Utils\UsersDisplayNameResolver\UsersDisplayNameResolverInterface;
use AngieApplication;
use ConversationUser;
use ConversationUsers;
use DataObjectPool;
use DB;
use IUser;
use User;
use Users;

class GroupConversation extends CustomConversation implements GroupConversationInterface
{
    public function getAdminIds(bool $use_cache = true): array
    {
        return Users::getAdminIdsForGroupConversation(
            $this,
            function () {
                $conditions = DB::prepare(
                    sprintf('m.%s = ? AND m.is_admin = ?', $this->getMembersFkName()),
                    $this->getId(),
                    true
                );
                $conditions .= DB::prepare(' AND u.is_archived = ? AND u.is_trashed = ?', false, false);

                return (array) DB::executeFirstColumn(
                    sprintf(
                        "SELECT u.id AS 'id' FROM users AS u LEFT JOIN %s AS m ON u.id = m.user_id WHERE %s ORDER BY u.id",
                        $this->getMembersTableName(),
                        $conditions
                    )
                );
            },
            $use_cache
        );
    }

    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'admins' => $this->getAdminIds(),
            ]
        );
    }

    public function canManage(User $user): bool
    {
        return $this->isMember($user) && ($user->isOwner() || $this->isAdmin($user));
    }

    public function isAdmin(User $user): bool
    {
        return $this->getConversationUser($user)->getIsAdmin();
    }

    public function setConversationUser(User $user, array $additional_fields = []): ConversationUser
    {
        $conversation_user = ConversationUsers::create(
            array_merge(
                [
                    'user_id' => $user->getId(),
                    'conversation_id' => $this->getId(),
                ],
                $additional_fields
            )
        );

        DataObjectPool::announce(new ConversationUserCreatedEvent($conversation_user));

        return $conversation_user;
    }

    public function leave(User $user): void
    {
        AngieApplication::getContainer()
            ->get(GroupConversationLeaveServiceInterface::class)
            ->leave($this, $user);
    }

    public function remove(User $user, User $by): GroupConversationInterface
    {
        return AngieApplication::getContainer()
            ->get(GroupConversationRemoveServiceInterface::class)
            ->remove($this, $user, $by);
    }

    public function rename(User $user, string $name): GroupConversationInterface
    {
        return AngieApplication::getContainer()
            ->get(GroupConversationRenameServiceInterface::class)
            ->rename($this, $user, $name);
    }

    public function setAdmin(User $user, User $by): GroupConversationInterface
    {
        return AngieApplication::getContainer()
            ->get(GroupConversationAdminServiceInterface::class)
            ->promoteToAdmin($this, $user, $by);
    }

    public function removeAdmin(User $user, User $by): GroupConversationInterface
    {
        return AngieApplication::getContainer()
            ->get(GroupConversationAdminServiceInterface::class)
            ->revokeAsAdmin($this, $user, $by);
    }

    public function invite(User $user, array $user_ids): GroupConversationInterface
    {
        return AngieApplication::getContainer()
            ->get(GroupConversationInviteServiceInterface::class)
            ->invite($this, $user, $user_ids);
    }

    public function getDisplayName(IUser $user): string
    {
        return !$this->getName()
            ? (string) AngieApplication::getContainer()
                ->get(UsersDisplayNameResolverInterface::class)
                ->getNameFor($this, $user, 'Group')
            : $this->getName();
    }
}
