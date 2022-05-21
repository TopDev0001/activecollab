<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Notes\Events\DataObjectLifeCycleEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use Note;

abstract class NoteLifeCycleEvent extends DataObjectLifeCycleEvent implements NoteLifeCycleEventInterface
{
    public function __construct(Note $object)
    {
        parent::__construct($object);
    }
}
