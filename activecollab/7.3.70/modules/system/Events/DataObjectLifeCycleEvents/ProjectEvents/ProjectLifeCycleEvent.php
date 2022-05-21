<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\ProjectEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use Project;
use Users;

abstract class ProjectLifeCycleEvent extends DataObjectLifeCycleEvent implements ProjectLifeCycleEventInterface
{
    private Project $object;

    public function __construct(Project $object)
    {
        parent::__construct($object);
        $this->object = $object;
    }

    public function whoShouldBeNotified(): array
    {
        return array_unique(
            array_merge(
                Users::findOwnerIds(),
                $this->object->getMemberIds()
            )
        );
    }
}
