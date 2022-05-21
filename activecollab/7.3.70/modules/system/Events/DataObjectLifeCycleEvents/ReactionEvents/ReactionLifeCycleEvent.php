<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ReactionEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use Reaction;

abstract class ReactionLifeCycleEvent extends DataObjectLifeCycleEvent implements ReactionLifeCycleEventInterface
{
    public function __construct(Reaction $object)
    {
        parent::__construct($object);
    }
}
