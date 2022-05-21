<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ConversationUserEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use ConversationUser;

abstract class ConversationUserLifeCycleEvent extends DataObjectLifeCycleEvent implements ConversationUserLifeCycleEventInterface
{
    public function __construct(ConversationUser $conversation_user)
    {
        parent::__construct($conversation_user);
    }
}
