<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\GhostTasks;

use DateValue;
use RecurringTask;

class GhostTask implements GhostTaskInterface
{
    private $recurring_task;
    private $id;
    private $start_on;
    private $due_on;
    private $next_trigger_on;

    public function __construct(
        RecurringTask $recurring_task,
        int $id,
        DateValue $start_on,
        DateValue $due_on,
        DateValue $next_trigger_on
    )
    {
        $this->recurring_task = $recurring_task;
        $this->id = $id;
        $this->start_on = $start_on;
        $this->due_on = $due_on;
        $this->next_trigger_on = $next_trigger_on;
    }

    public function getRecurringTask(): RecurringTask
    {
        return $this->recurring_task;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStartOn(): DateValue
    {
        return $this->start_on;
    }

    public function getDueOn(): DateValue
    {
        return $this->due_on;
    }

    public function getNextTriggerOn(): DateValue
    {
        return $this->next_trigger_on;
    }

    public function jsonSerialize(): array
    {
        return [
            'class' => 'GhostTask',
            'id' => $this->id,
            'project_id' => $this->recurring_task->getProjectId(),
            'project_name' => $this->recurring_task->getProject()->getName(),
            'assignee_id' => $this->recurring_task->getAssigneeId(),
            'name' => $this->recurring_task->getName(),
            'start_on' => $this->start_on->getTimestamp(),
            'due_on' => $this->due_on->getTimestamp(),
            'created_on' => $this->next_trigger_on->getTimestamp(),
            'is_hidden_form_clients' => $this->recurring_task->getIsHiddenFromClients(),
            'is_important' => $this->recurring_task->getIsImportant(),
            'url_path' => $this->recurring_task->getUrlPath(),
            'start_in' => $this->start_on->format('Y-m-d'),
            'due_in' => $this->due_on->format('Y-m-d'),
            'created_on1' => $this->next_trigger_on->format('Y-m-d'),
            'repeat_frequency' => $this->recurring_task->getRepeatFrequency(),
        ];
    }
}
