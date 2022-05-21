<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateInappropriateTaskDateValueToNull extends AngieModelMigration
{
    public function up()
    {
        [$tasks_table] = $this->useTables('tasks');

        $minDate = DateValue::makeFromString('2000-01-01');
        $maxDate = DateValue::now()->addDays(365 * 20);

        $rows = DB::execute(
            "SELECT `id`, `start_on`, `due_on` FROM `$tasks_table` WHERE `start_on` IS NOT NULL AND `due_on` IS NOT NULL AND ((`start_on` NOT BETWEEN ? AND ?) OR (`due_on` NOT BETWEEN ? AND ?))",
            $minDate,
            $maxDate,
            $minDate,
            $maxDate
        );

        if ($rows) {
            $rows->setCasting([
                'start_on' => DBResult::CAST_DATE,
                'due_on' => DBResult::CAST_DATE,
            ]);

            $start_on_task_ids = [];
            $due_on_task_ids = [];
            $task_ids = [];

            foreach ($rows as $row) {
                AngieApplication::log()->info(
                    'Task whose dates are going to be modified by new range policy.',
                    [
                        'task_id' => $row['id'],
                        'start_on' => $row['start_on']->toMySQL(),
                        'due_on' => $row['due_on']->toMySQL(),
                    ]
                );

                if (
                    ($row['start_on']->getTimestamp() < $minDate->getTimestamp() || $maxDate->getTimestamp() < $row['start_on']->getTimestamp()) &&
                    $minDate->getTimestamp() <= $row['due_on']->getTimestamp() && $row['due_on']->getTimestamp() <= $maxDate->getTimestamp()
                ) {
                    $start_on_task_ids[] = $row['id'];
                } elseif (
                    $minDate->getTimestamp() <= $row['start_on']->getTimestamp() && $row['start_on']->getTimestamp() <= $maxDate->getTimestamp() &&
                    ($row['due_on']->getTimestamp() < $minDate->getTimestamp() || $maxDate->getTimestamp() < $row['due_on']->getTimestamp())
                ) {
                    $due_on_task_ids[] = $row['id'];
                } elseif (
                    ($row['start_on']->getTimestamp() < $minDate->getTimestamp() || $maxDate->getTimestamp() < $row['start_on']->getTimestamp()) &&
                    ($row['due_on']->getTimestamp() < $minDate->getTimestamp() || $maxDate->getTimestamp() < $row['due_on']->getTimestamp())
                ) {
                    $task_ids[] = $row['id'];
                }
            }

            // Start out of range
            if ($start_on_task_ids) {
                DB::execute(
                    "UPDATE `$tasks_table` SET `start_on` = `due_on` WHERE `id` IN (?)",
                    $start_on_task_ids
                );
            }

            // Due out of range
            if ($due_on_task_ids) {
                DB::execute(
                    "UPDATE `$tasks_table` SET `due_on` = `start_on` WHERE `id` IN (?)",
                    $due_on_task_ids
                );
            }

            // Start and Due out of range
            if ($task_ids) {
                DB::execute(
                    "UPDATE `$tasks_table` SET `start_on` = NULL, `due_on` = NULL WHERE `id` IN (?)",
                    $task_ids
                );
            }
        }

        $this->doneUsingTables();
    }
}
