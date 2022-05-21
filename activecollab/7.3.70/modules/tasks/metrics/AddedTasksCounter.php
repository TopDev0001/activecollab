<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\Tasks\Metric;

final class AddedTasksCounter extends TasksCounter
{
    protected function prepareConditions($from_timestamp, $until_timestamp, array $conditions)
    {
        return array_merge(
            $conditions,
            [
                $this->connection->prepare(
                    '`created_on` BETWEEN ? AND ?',
                    [
                        $from_timestamp,
                        $until_timestamp,
                    ]
                ),
            ]
        );
    }
}
