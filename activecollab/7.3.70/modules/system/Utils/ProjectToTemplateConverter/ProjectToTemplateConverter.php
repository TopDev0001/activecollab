<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\ProjectToTemplateConverter;

use DateTimeValue;
use DateValue;
use DBBatchInsert;
use DBConnection;
use Exception;
use Project;
use ProjectTemplate;
use ProjectTemplateElements;
use ProjectTemplates;
use ProjectTemplateSubtask;
use ProjectTemplateTask;
use ProjectTemplateTaskList;
use Task;
use TaskLists;
use Tasks;

class ProjectToTemplateConverter implements ProjectToTemplateConverterInterface
{
    private $connection;

    public function __construct(DBConnection $connection)
    {
        $this->connection = $connection;
    }

    public function convertProjectToTemplate(
        Project $project,
        string $template_name = null
    ): ProjectTemplate
    {
        if (empty($template_name)) {
            $template_name = $project->getName();
        }

        try {
            $this->connection->beginWork();

            $project_member_ids = $this->getMemberIds($project);

            /** @var ProjectTemplate $template */
            $template = ProjectTemplates::create(
                [
                    'name' => $template_name,
                    'members' => $project_member_ids,
                ]
            );

            $converted_task_lists = $this->convertTaskLists($project, $template);
            $converted_tasks = $this->convertTasks($project, $converted_task_lists, $project_member_ids, $template);

            $this->convertSubtasks($project, $converted_tasks, $project_member_ids, $template);
            $this->convertDependencies($project, $converted_tasks);

            $this->connection->commit();

            return $template;
        } catch (Exception $e) {
            $this->connection->rollback();
            throw $e;
        }
    }

    private function getMemberIds(Project $project): array
    {
        return $project->getMemberIds(false);
    }

    private function convertTaskLists(Project $project, ProjectTemplate $template): array
    {
        $result = [];

        $task_list_names = TaskLists::getIdNameMap($project);

        if ($task_list_names) {
            $task_list_position = 0;

            foreach ($task_list_names as $task_list_id => $task_list_name) {
                $template_list = ProjectTemplateElements::create(
                    [
                        'type' => ProjectTemplateTaskList::class,
                        'template_id' => $template->getId(),
                        'name' => $task_list_name,
                        'position' => ++$task_list_position,
                    ]
                );

                $result[$task_list_id] = $template_list->getId();
            }
        }

        return $result;
    }

    private function convertTasks(
        Project $project,
        array $converted_task_lists,
        array $project_member_ids,
        ProjectTemplate $template
    ): array
    {
        $dates_map = $this->getDatesMap($project);

        $result = [];

        /** @var Task[] $tasks */
        $tasks = Tasks::find(
            [
                'conditions' => [
                    '`project_id` = ? AND `is_trashed` = ?',
                    $project->getId(),
                    false,
                ],
                'order' => 'position',
            ]
        );

        if ($tasks) {
            $task_position = 0;

            foreach ($tasks as $task) {
                $template_task = ProjectTemplateElements::create(
                    array_merge(
                        $this->getTaskAttributes(
                            $task,
                            $converted_task_lists,
                            $project_member_ids,
                            $dates_map,
                            $template
                        ),
                        [
                            'position' => ++$task_position,
                        ]
                    )
                );

                $task->cloneAttachmentsTo($template_task);
                $task->cloneLabelsTo($template_task);

                $result[$task->getId()] = $template_task->getId();
            }
        }

        return $result;
    }

    private function convertSubtasks(
        Project $project,
        array $converted_tasks,
        array $project_member_ids,
        ProjectTemplate $template
    ): void
    {
        $subtasks = $this->connection->execute(
            'SELECT `id`, `body`, `task_id`, `assignee_id`
                FROM `subtasks`
                WHERE `task_id` IN (SELECT `id` FROM `tasks` WHERE `project_id` = ? AND `is_trashed` = ?)
                ORDER BY `position`',
            [
                $project->getId(),
                false,
            ]
        );

        if ($subtasks) {
            $subtask_position = 0;

            foreach ($subtasks as $subtask) {
                $task_id = $converted_tasks[$subtask['task_id']] ?? null;

                $subtask_attributes = [
                    'type' => ProjectTemplateSubtask::class,
                    'template_id' => $template->getId(),
                    'task_id' => $task_id,
                    'assignee_id' => $subtask['assignee_id'],
                    'body' => $subtask['body'],
                    'position' => ++$subtask_position,
                ];

                if (in_array($subtask['assignee_id'], $project_member_ids)) {
                    $subtask_attributes['assignee_id'] = $subtask['assignee_id'];
                }

                ProjectTemplateElements::create($subtask_attributes);
            }
        }
    }

    private function convertDependencies(
        Project $project,
        array $converted_tasks
    ): void
    {
        if (empty($converted_tasks)) {
            return;
        }

        $dependencies = $this->connection->execute(
            'SELECT `parent_id`, `child_id`
                FROM `task_dependencies`
                WHERE `parent_id` IN (SELECT `id` FROM `tasks` WHERE `project_id` = ? AND `is_trashed` = ?)
                    OR `parent_id` IN (SELECT `id` FROM `tasks` WHERE `project_id` = ? AND `is_trashed` = ?)',
            [
                $project->getId(),
                false,
                $project->getId(),
                false,
            ]
        );

        if ($dependencies) {
            $now = new DateTimeValue();

            $batch = new DBBatchInsert(
                'project_template_task_dependencies',
                [
                    'parent_id',
                    'child_id',
                    'created_on',
                ],
                50,
                DBBatchInsert::REPLACE_RECORDS
            );

            foreach ($dependencies as $dependency) {
                $parent_id = $converted_tasks[$dependency['parent_id']] ?? null;
                $child_id = $converted_tasks[$dependency['child_id']] ?? null;

                if ($parent_id && $child_id) {
                    $batch->insert(
                        $converted_tasks[$dependency['parent_id']],
                        $converted_tasks[$dependency['child_id']],
                        $now
                    );
                }
            }

            $batch->done();
        }
    }

    private function getTaskAttributes(
        Task $task,
        array $converted_task_lists,
        array $project_member_ids,
        array $dates_map,
        ProjectTemplate $template
    ): array
    {
        $result = [
            'type' => ProjectTemplateTask::class,
            'template_id' => $template->getId(),
            'task_list_id' => $converted_task_lists[$task->getTaskListId()] ?? first($converted_task_lists),
            'name' => $task->getName(),
            'body' => $task->getBody(),
            'is_important' => $task->getIsImportant(),
            'is_hidden_from_clients' => $task->getIsHiddenFromClients(),
        ];

        if (in_array($task->getAssigneeId(), $project_member_ids)) {
            $result['assignee_id'] = $task->getAssigneeId();
        }

        if ($task->getEstimate() && $task->getJobTypeId()) {
            $result['estimate'] = $task->getEstimate();
            $result['job_type_id'] = $task->getJobTypeId();
        }

        [
            $start_on,
            $due_on,
        ] = $this->getTaskDateRange($task, $dates_map);

        if ($start_on && $due_on && $due_on >= $start_on) {
            $result['start_on'] = $start_on;
            $result['due_on'] = $due_on;
        }

        return $result;
    }

    private function getTaskDateRange(Task $task, array $dates_map): array
    {
        if (!empty($dates_map) && $task->getStartOn() && $task->getDueOn()) {
            $start_on = $task->getStartOn()->format('Y-m-d');
            $due_on = $task->getDueOn()->format('Y-m-d');

            if (array_key_exists($start_on, $dates_map) && array_key_exists($due_on, $dates_map)) {
                return [
                    $dates_map[$start_on],
                    $dates_map[$due_on],
                ];
            }
        }

        return [0, 0];
    }

    private function getDatesMap(Project $project): array
    {
        $row = $this->connection->executeFirstRow(
            'SELECT MIN(`start_on`) AS "from", MAX(`due_on`) AS "to" FROM `tasks` WHERE `project_id` = ? AND `is_trashed` = ?',
            [
                $project->getId(),
                false,
            ]
        );

        $result = [];

        if (!empty($row) && !empty($row['from']) && !empty($row['to'])) {
            $from = new DateValue($row['from']);
            $to = new DateValue($row['to']);

            $counter = 0;

            DateValue::iterateDaily(
                $from,
                $to,
                function (DateValue $current_date) use (&$result, &$counter) {
                    if ($current_date->isWorkday() && !$current_date->isDayOff()) {
                        $result[$current_date->format('Y-m-d')] = ++$counter;
                    }
                }
            );
        }

        return $result;
    }
}
