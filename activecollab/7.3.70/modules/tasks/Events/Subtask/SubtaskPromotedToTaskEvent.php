<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Events\Subtask;

use ActiveCollab\EventsDispatcher\Events\Event;
use Task;
use User;

class SubtaskPromotedToTaskEvent extends Event implements SubtaskPromotedToTaskEventInterface
{
    private Task $from_task;
    private Task $to_task;
    private User $user;

    public function __construct(Task $from_task, Task $to_task, User $user)
    {
        $this->from_task = $from_task;
        $this->to_task = $to_task;
        $this->user = $user;
    }

    public function getFromTask(): Task
    {
        return $this->from_task;
    }

    public function getToTask(): Task
    {
        return $this->to_task;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
