<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Events\Subtask;

use ActiveCollab\EventsDispatcher\Events\EventInterface;
use Task;
use User;

interface SubtaskPromotedToTaskEventInterface extends EventInterface
{
    public function getFromTask(): Task;

    public function getToTask(): Task;

    public function getUser(): User;
}
