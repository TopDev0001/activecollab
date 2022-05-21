<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateInappropriateRecurringTaskAndTaskTemplateDatesToNull extends AngieModelMigration
{
    public function up()
    {
        $min_days = -1 * DateValue::makeFromString('2000-01-01')->daysBetween(new DateValue());
        $max_days = 365 * 20;

        $recurring_tasks = DB::execute(
            'SELECT id, name, start_in, due_in
             FROM recurring_tasks
             WHERE `start_in` IS NOT NULL AND `due_in` IS NOT NULL AND ((`start_in` NOT BETWEEN ? AND ?) OR (`due_in` NOT BETWEEN ? AND ?))',
            $min_days,
            $max_days,
            $min_days,
            $max_days
        );

        if ($recurring_tasks) {
            $recurring_tasks->setCasting([
                'start_in' => DBResult::CAST_INT,
                'due_in' => DBResult::CAST_INT,
            ]);

            $start_in_task_ids = [];
            $due_in_task_ids = [];
            $recurring_task_ids = [];

            foreach ($recurring_tasks as $task) {
                AngieApplication::log()->info(
                    'Recurring task whose dates are going to be modified by new range policy.',
                    [
                        'task_id' => $task['id'],
                        'task_name' => $task['name'],
                        'start_in' => $task['start_in'],
                        'due_in' => $task['due_in'],
                    ]
                );

                if (
                    ($task['start_in'] < $min_days || $task['start_in'] > $max_days) &&
                    $min_days <= $task['due_in'] && $task['due_in'] <= $max_days
                ) {
                    $start_in_task_ids[] = $task['id'];
                } elseif (
                    $min_days <= $task['start_in'] &&
                    $task['start_in'] <= $max_days &&
                    ($task['due_in'] < $min_days || $max_days < $task['due_in'])
                ) {
                    $due_in_task_ids[] = $task['id'];
                } elseif (
                    ($task['start_in'] < $min_days || $max_days < $task['start_in']) &&
                    ($task['due_in'] < $min_days || $max_days < $task['due_in'])
                ) {
                    $recurring_task_ids[] = $task['id'];
                }
            }

            // Start In out of range
            if ($start_in_task_ids) {
                DB::execute(
                    'UPDATE `recurring_tasks` SET `start_in` = `due_in` WHERE `id` IN (?)',
                    $start_in_task_ids
                );
            }

            // Due In out of range
            if ($due_in_task_ids) {
                DB::execute(
                    'UPDATE `recurring_tasks` SET `due_in` = `start_in` WHERE `id` IN (?)',
                    $due_in_task_ids
                );
            }

            // Start In and Due In out of range
            if ($recurring_task_ids) {
                DB::execute(
                    'UPDATE `recurring_tasks` SET `start_in` = NULL, `due_in` = NULL WHERE `id` IN (?)',
                    $recurring_task_ids
                );
            }
        }

        /** @var ProjectTemplateElement[] $task_templates */
        $task_templates = ProjectTemplateElements::find(
            [
                'condition' => [
                    'type = ?',
                    ProjectTemplateTask::class,
                ],
            ]
        );

        if ($task_templates) {
            foreach ($task_templates as $task_template) {
                $attributes = unserialize($task_template->getFieldValue('raw_additional_properties'));

                if (
                    is_array($attributes) &&
                    array_key_exists('start_on', $attributes) &&
                    array_key_exists('due_on', $attributes) &&
                    ($attributes['start_on'] > $max_days || $attributes['due_on'] > $max_days)
                ) {
                    AngieApplication::log()->info(
                        'Task template whose dates are going to be modified by new range policy.',
                        [
                            'task_template_id' => $task_template->getId(),
                            'name' => $task_template->getName(),
                            'start_on' => (int) $task_template->getAdditionalProperty('start_on'),
                            'due_on' => (int) $task_template->getAdditionalProperty('due_on'),
                        ]
                    );

                    if ($attributes['start_on'] > $max_days && $attributes['due_on'] > $max_days) {
                        $task_template->setAdditionalProperty('start_on', 0);
                        $task_template->setAdditionalProperty('due_on', 0);
                    } elseif ($attributes['start_on'] > $max_days) {
                        $task_template->setAdditionalProperty(
                            'start_on',
                            $task_template->getAdditionalProperty('due_on')
                        );
                    } else {
                        $task_template->setAdditionalProperty(
                            'due_on',
                            $task_template->getAdditionalProperty('start_on')
                        );
                    }

                    $task_template->save();
                }
            }
        }
    }
}
