<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\Tasks\Metric\AddedTasksCounter;
use ActiveCollab\Module\Tasks\Metric\CompletedTasksCounter;
use ActiveCollab\Module\Tasks\Metric\TasksCollection;

function tasks_handle_on_extra_stats(array &$stats, $date)
{
    (new TasksCollection(DB::getConnection()))->getValueFor($date)->addTo($stats);
    (new AddedTasksCounter(DB::getConnection()))->getValueFor($date)->addTo($stats);
    (new CompletedTasksCounter(DB::getConnection()))->getValueFor($date)->addTo($stats);
}
