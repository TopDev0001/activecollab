<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Model\Conversation;

use ConversationUser;
use User;

interface GroupConversationInterface extends CustomConversationInterface
{
    public function leave(User $user): void;

    public function remove(User $user, User $by): GroupConversationInterface;

    public function rename(User $user, string $name): GroupConversationInterface;

    public function canManage(User $user): bool;

    public function getAdminIds(bool $use_cache = true): array;

    public function isAdmin(User $user): bool;

    public function setAdmin(User $user, User $by): GroupConversationInterface;

    public function removeAdmin(User $user, User $by): GroupConversationInterface;

    public function invite(User $user, array $user_ids): GroupConversationInterface;

    public function setConversationUser(User $user, array $additional_fields = []): ConversationUser;
}
