<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Model\Conversation;

use ActiveCollab\Module\System\Utils\OwnerCompanyResolver\OwnerCompanyResolverInterface;
use AngieApplication;
use Client;
use DB;
use IUser;

class GeneralConversation extends SmartConversation
{
    public function getMemberIds(bool $use_cache = true): array
    {
        return AngieApplication::cache()->getByObject($this, 'member_ids', function () {
            return DB::executeFirstColumn(
                    'SELECT id FROM users WHERE company_id = ? AND is_archived = ? AND is_trashed = ? AND type <> ? ORDER BY id',
                    AngieApplication::getContainer()
                        ->get(OwnerCompanyResolverInterface::class)
                            ->getId(),
                    false,
                    false,
                    Client::class
                ) ?? [];
        }, empty($use_cache));
    }

    public function getExtendedTimestampValue(): string
    {
        return implode(',', $this->getMemberIds());
    }

    public function getDisplayName(IUser $user): string
    {
        return 'General';
    }
}
