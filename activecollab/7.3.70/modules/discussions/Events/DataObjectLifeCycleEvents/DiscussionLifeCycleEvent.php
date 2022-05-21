<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Discussions\Events\DataObjectLifeCycleEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use Discussion;

abstract class DiscussionLifeCycleEvent extends DataObjectLifeCycleEvent implements DiscussionLifeCycleEventInterface
{
    public function __construct(Discussion $object)
    {
        parent::__construct($object);
    }
}
