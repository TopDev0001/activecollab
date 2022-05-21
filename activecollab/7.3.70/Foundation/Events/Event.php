<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Events;

use ActiveCollab\EventsDispatcher\Events\Event as BaseEvent;

abstract class Event extends BaseEvent implements EventInterface
{
}
