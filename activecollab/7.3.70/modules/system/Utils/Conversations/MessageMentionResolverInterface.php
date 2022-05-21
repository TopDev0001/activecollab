<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\System\Utils\Conversations;

use Message;

interface MessageMentionResolverInterface
{
    public function resolve(Message $message): void;
}
