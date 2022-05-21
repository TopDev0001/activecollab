<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Model\Conversation;

use IUser;

abstract class SmartConversation extends \Conversation
{
    public function getTag(IUser $user, $use_cache = true): string
    {
        return '"' . implode(
                ',',
                [
                    APPLICATION_VERSION,
                    'object',
                    $this->getModelName(),
                    $this->getId(),
                    $user->getEmail(),
                    sha1(
                        sprintf(
                            '%s%s%s',
                            APPLICATION_UNIQUE_KEY,
                            $this->getUpdatedOn()->toMySQL(),
                            $this->getExtendedTimestampValue()
                        )
                    ),
                ]
            ) . '"';
    }

    abstract public function getExtendedTimestampValue(): string;
}
