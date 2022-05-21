<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\GhostTasks;

use DateValue;
use JsonSerializable;
use RecurringTask;

interface GhostTaskInterface extends JsonSerializable
{
    public function getId(): int;
    public function getStartOn(): DateValue;
    public function getDueOn(): DateValue;
    public function getNextTriggerOn(): DateValue;
    public function getRecurringTask(): RecurringTask;
}
