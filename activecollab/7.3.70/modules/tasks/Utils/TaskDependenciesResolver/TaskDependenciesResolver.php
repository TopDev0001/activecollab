<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\TaskDependenciesResolver;

use DB;
use ITaskDependencies;
use IUser;
use ModelCollection;
use Task;
use TaskDependencies;
use TaskDependenciesCollection;
use TaskDependenciesSuggestionsCollection;

class TaskDependenciesResolver implements TaskDependenciesResolverInterface
{
    private $user;

    public function __construct(IUser $user)
    {
        $this->user = $user;
    }

    public function getDependencies(ITaskDependencies $task): TaskDependenciesCollection
    {
        return TaskDependencies::prepareCollection(
            "task_dependencies_for_{$task->getId()}",
            $this->user
        );
    }

    public function getDependencySuggestions(ITaskDependencies $task): TaskDependenciesSuggestionsCollection
    {
        return TaskDependencies::prepareCollection(
            "suggestion_task_dependencies_for_{$task->getId()}",
            $this->user
        );
    }

    public function getProjectDependenciesCollection(int $project_id): ModelCollection
    {
        return TaskDependencies::prepareCollection(
            "project_dependencies_for_{$project_id}",
            $this->user
        );
    }

    public function getProjectDependencies(int $project_id): array
    {
        $task_ids = DB::executeFirstColumn('SELECT id FROM tasks WHERE project_id = ?', $project_id);

        $task_dependencies = DB::execute(
            'SELECT * FROM task_dependencies WHERE parent_id IN (?) OR child_id IN (?)',
            $task_ids,
            $task_ids
        );

        return $task_dependencies ? $task_dependencies->toArray() : [];
    }

    public function countOpenDependencies(Task $task): array
    {
        $condition = $this->user->isClient()
            ? DB::prepare('t.completed_on IS NULL AND t.is_trashed = ? AND t.is_hidden_from_clients = ?', false, false)
            : DB::prepare('t.completed_on IS NULL AND t.is_trashed = ?', false);

        $parent_ids = DB::executeFirstColumn(
            "SELECT t.id
            FROM tasks t
            JOIN task_dependencies td ON t.id = td.parent_id
            WHERE td.child_id = ? AND $condition",
            $task->getId()
        );

        $child_ids = DB::executeFirstColumn(
            "SELECT t.id
            FROM tasks t
            JOIN task_dependencies td ON t.id = td.child_id
            WHERE td.parent_id = ? AND $condition",
            $task->getId()
        );

        return [
            'parents_count' => $parent_ids ? count($parent_ids) : 0,
            'children_count' => $child_ids ? count($child_ids) : 0,
        ];
    }

    public function isTaskBetweenScheduledDependencies(Task $task): bool
    {
        $scheduled_parent_exists = false;
        $scheduled_child_exists = false;

        /** @var Task $parent */
        foreach ($task->getParentDependencies() as $parent) {
            if ($parent->getDueOn()) {
                $scheduled_parent_exists = true;
                break;
            }
        }

        /** @var Task $child */
        foreach ($task->getChildDependencies() as $child) {
            if ($child->getDueOn()) {
                $scheduled_child_exists = true;
                break;
            }
        }

        return $scheduled_parent_exists && $scheduled_child_exists;
    }

    public function getTaskDependenciesSuggestionIds(int $task_id, int $project_id, IUser $user): array
    {
        $query = '
            SELECT t.id
            FROM tasks t
            WHERE t.id != ?
                AND t.project_id = ?
                AND t.id NOT IN
                    (
                      SELECT td1.child_id
                      FROM task_dependencies td1
                      WHERE td1.parent_id = ?
                    )
                AND t.id NOT IN
                    (
                      SELECT td2.parent_id
                      FROM task_dependencies td2
                      WHERE td2.child_id = ?
                    )
                AND t.completed_on IS NULL
                AND t.is_trashed = 0
        ';

        if ($user->isClient()) {
            $query = $query . ' AND t.is_hidden_from_clients = 0';
        }

        $suggestion_task_ids = DB::executeFirstColumn($query, $task_id, $project_id, $task_id, $task_id);

        return $suggestion_task_ids ? $suggestion_task_ids : [];
    }
}
