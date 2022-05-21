<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateLogTasksWithUnexpectedDates extends AngieModelMigration
{
    public function up()
    {
        $max_due_days = 5 * 365;

        $tasks = DB::execute(
            'SELECT t.id, t.name, start_on, due_on, p.completed_on, p.trashed_on
             FROM tasks t
             LEFT JOIN projects p ON p.id = t.project_id
             WHERE t.start_on < ? OR t.due_on > ?',
            new DateValue('2006-01-01'),
            DateValue::now()->addDays($max_due_days)
        );

        if (!empty($tasks)) {
            foreach ($tasks as $task) {
                AngieApplication::log()->info(
                    'Unexpected dates for task.',
                    [
                        'task_id' => $task['id'],
                        'name' => $task['name'],
                        'start_on' => $task['start_on'],
                        'due_on' => $task['due_on'],
                        'project_status' => $task['completed_on'] ? 'Completed' : 'Open',
                        'is_trashed_project' => $task['trashed_on'] !== null,
                    ]
                );
            }
        }

        $recurring_tasks = DB::execute(
            'SELECT t.id, t.name, t.start_in, t.due_in, p.completed_on, p.trashed_on
             FROM recurring_tasks t
             LEFT JOIN projects p ON p.id = t.project_id
             WHERE t.due_in > ?',
            $max_due_days
        );

        if (!empty($recurring_tasks)) {
            foreach ($recurring_tasks as $task) {
                AngieApplication::log()->info(
                    'Unexpected dates for reccuring task.',
                    [
                        'reccuring_task_id' => $task['id'],
                        'name' => $task['name'],
                        'start_in' => $task['start_in'],
                        'due_in' => $task['due_in'],
                        'project_status' => $task['completed_on'] ? 'Completed' : 'Open',
                        'is_trashed_project' => $task['trashed_on'] !== null,
                    ]
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

                if (!empty($attributes['due_on']) && $attributes['due_on'] > $max_due_days) {
                    AngieApplication::log()->info(
                        'Unexpected dates for task template.',
                        [
                            'task_template_id' => $task_template->getId(),
                            'name' => $task_template->getName(),
                            'start_on' => (int) $task_template->getAdditionalProperty('start_on'),
                            'due_on' => (int) $task_template->getAdditionalProperty('due_on'),
                        ]
                    );
                }
            }
        }
    }
}
