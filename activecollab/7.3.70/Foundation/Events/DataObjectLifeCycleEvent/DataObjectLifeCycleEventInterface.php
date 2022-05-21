<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent;

use ActiveCollab\Foundation\Events\EventInterface;
use DataObject;

interface DataObjectLifeCycleEventInterface extends EventInterface
{
    public function getObject(): DataObject;
    public function whoShouldBeNotified(): array;
}
