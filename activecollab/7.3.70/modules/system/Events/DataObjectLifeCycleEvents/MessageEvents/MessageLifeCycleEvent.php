<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\MessageEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use Message;

abstract class MessageLifeCycleEvent extends DataObjectLifeCycleEvent implements MessageLifeCycleEventInterface
{
    public function __construct(Message $message)
    {
        parent::__construct($message);
    }
}
