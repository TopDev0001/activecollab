<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\Conversations;

use ActiveCollab\Foundation\Wrappers\Cache\CacheInterface;
use ActiveCollab\Module\System\Model\Conversation\GroupConversationInterface;
use DB;

class GroupConversationAdminGenerator implements GroupConversationAdminGeneratorInterface
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function generate(
        GroupConversationInterface $conversation,
        array $exclude_ids = []
    ): GroupConversationInterface
    {
        $member_ids = $conversation->getMemberIds();

        if (empty($member_ids) || !empty($conversation->getAdminIds(false))) {
            return $conversation; // skip generating conversation admins if they already exist
        }

        $created_by_id = $conversation->getCreatedById();

        if ($this->shouldCreatorOfConversationBecomeAdmin($created_by_id, $member_ids, $exclude_ids)) {
            DB::execute(
                'UPDATE conversation_users SET is_admin = ? WHERE conversation_id = ? AND user_id = ?',
                true,
                $conversation->getId(),
                $created_by_id
            );
        } else {
            DB::execute(
                'UPDATE conversation_users SET is_admin = ? WHERE id = ?',
                true,
                (int) DB::executeFirstCell(
                    'SELECT MIN(id) FROM conversation_users WHERE conversation_id = ? AND user_id IN (?) AND user_id NOT IN (?)',
                    $conversation->getId(),
                    $member_ids,
                    array_unique(
                        array_merge(
                            $exclude_ids,
                            [$created_by_id]
                        )
                    )
                )
            );
        }

        $this->cache->removeByObject($conversation);

        return $conversation;
    }

    private function shouldCreatorOfConversationBecomeAdmin(int $id, array $member_ids, array $exclude_ids): bool
    {
        return in_array($id, $member_ids) && !in_array($id, $exclude_ids);
    }
}
