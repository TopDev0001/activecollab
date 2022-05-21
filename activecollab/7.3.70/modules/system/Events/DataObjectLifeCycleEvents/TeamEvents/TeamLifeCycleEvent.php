<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\TeamEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use Team;

abstract class TeamLifeCycleEvent extends DataObjectLifeCycleEvent implements TeamLifeCycleEventInterface
{
    public function __construct(Team $team)
    {
        parent::__construct($team);
    }
}
