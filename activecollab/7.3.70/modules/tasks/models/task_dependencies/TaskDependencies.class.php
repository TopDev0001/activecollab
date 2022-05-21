<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Tasks\Events\DataObjectLifeCycleEvents\TaskEvents\TaskUpdatedEvent;

class TaskDependencies extends BaseTaskDependencies
{
    public static function createDependency(Task $parent, Task $child, IUser $by)
    {
        $new_dependency = [
            'parent_id' => $parent->getId(),
            'child_id' => $child->getId(),
        ];

        $dependencies = AngieApplication::taskDependenciesResolver($by)
            ->getProjectDependencies($parent->getProjectId());

        if (count($dependencies)) {
            $dependencies[] = $new_dependency;
        }

        if (AngieApplication::cyclicDependencyResolver()->checkCyclicDependency($dependencies)) {
            throw new LogicException(lang('Circular dependency detected, action aborted.'));
        }

        $task_dependency = null;

        DB::transact(
            function () use ($new_dependency, $by, $parent, $child, &$task_dependency) {
                $task_dependency = self::create($new_dependency);

                $parent->touch($by);
                $child->touch($by);

                $simulation = AngieApplication::scheduleDependenciesChainsService()->makeSimulation($parent, $child);

                AngieApplication::taskDateRescheduler()->updateSimulationTaskDates($parent, $simulation, $by);
            }
        );

        if ($task_dependency) {
            DataObjectPool::announce(new TaskUpdatedEvent($parent));
            DataObjectPool::announce(new TaskUpdatedEvent($child));
        }

        return $task_dependency;
    }

    public static function deleteDependency(Task $task_1, Task $task_2, IUser $by)
    {
        parent::delete(
            [
                '(parent_id = ? && child_id = ?) || (parent_id = ? && child_id = ?)',
                $task_1->getId(),
                $task_2->getId(),
                $task_2->getId(),
                $task_1->getId(),
            ]
        );

        $task_1->touch($by);
        $task_2->touch($by);
    }

    private static $open_dependencies_counts = [];

    public static function resetState()
    {
        self::$open_dependencies_counts = [];
    }

    public static function preloadCountByTasks(array $task_ids)
    {
        $parent_sql = DB::prepare(
            "SELECT parent_id, COUNT(parent_id) AS 'parents_count'
                FROM task_dependencies td
                LEFT JOIN tasks t ON t.id = td.child_id
                WHERE td.child_id IN (?) AND t.is_trashed = ? AND t.completed_on IS NULL
                GROUP BY parent_id",
            $task_ids,
            false
        );

        if ($rows = DB::execute($parent_sql)) {
            foreach ($rows as $row) {
                self::$open_dependencies_counts[$row['parent_id']]['children_count'] = (int) $row['parents_count'];
                self::$open_dependencies_counts[$row['parent_id']]['parents_count'] = 0;
            }
        }

        $child_sql = DB::prepare(
            "SELECT child_id, COUNT(child_id) AS 'children_count'
                FROM task_dependencies td
                LEFT JOIN tasks t ON t.id = td.parent_id
                WHERE td.parent_id IN (?) AND t.is_trashed = ? AND t.completed_on IS NULL
                GROUP BY child_id",
            $task_ids,
            false
        );

        if ($rows = DB::execute($child_sql)) {
            foreach ($rows as $row) {
                self::$open_dependencies_counts[$row['child_id']]['parents_count'] = (int) $row['children_count'];
                if (!isset(self::$open_dependencies_counts[$row['child_id']]['children_count'])) {
                    self::$open_dependencies_counts[$row['child_id']]['children_count'] = 0;
                }
            }
        }

        if ($zeros = array_diff($task_ids, array_keys(self::$open_dependencies_counts))) {
            foreach ($zeros as $task_with_zero_dependencies) {
                self::$open_dependencies_counts[$task_with_zero_dependencies] = [
                    'parents_count' => 0,
                    'children_count' => 0,
                ];
            }
        }
    }

    public static function countOpenDependenciesFromPreloadedValue(int $task_id)
    {
        if (isset(self::$open_dependencies_counts[$task_id])) {
            return self::$open_dependencies_counts[$task_id];
        } else {
            return 0;
        }
    }

    public static function countOpenDependencies(Task $task): array
    {
        $task_id = $task->getId();

        if (isset(self::$open_dependencies_counts[$task_id])) {
            return self::$open_dependencies_counts[$task_id];
        } else {
            return AngieApplication::taskDependenciesResolver(
                AngieApplication::authentication()->getAuthenticatedUser()
            )->countOpenDependencies($task);
        }
    }

    public static function deleteByTask(Task $task)
    {
        self::delete(
            [
                'parent_id = ? || child_id = ?',
                $task->getId(),
                $task->getId(),
            ]
        );
    }

    /**
     * Return new collection.
     *
     * @param  User|IUser|null $user
     * @return ModelCollection
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);

        if (str_starts_with($collection_name, 'suggestion_task_dependencies')) {
            return self::prepareTaskDependenciesSuggestionsCollection($collection_name, $user);
        } elseif (str_starts_with($collection_name, 'task_dependencies')) {
            return self::prepareTaskDependenciesCollection($collection_name, $user);
        } elseif (str_starts_with($collection_name, 'project_dependencies')) {
            return self::prepareProjectDependenciesCollection($collection, $collection_name, $user);
        }

        return $collection;
    }

    public static function prepareTaskDependenciesSuggestionsCollection($collection_name, $user)
    {
        $bits = explode('_', $collection_name);

        /** @var Task $task */
        if ($task = DataObjectPool::get(Task::class, array_pop($bits))) {
            return (new TaskDependenciesSuggestionsCollection($collection_name))
                ->setTask($task)
                ->setWhosAsking($user);
        } else {
            throw new InvalidParamError('collection_name', $collection_name, 'Task ID expected in collection name');
        }
    }

    private static function prepareTaskDependenciesCollection($collection_name, $user)
    {
        $bits = explode('_', $collection_name);

        /** @var Task $task */
        if ($task = DataObjectPool::get(Task::class, array_pop($bits))) {
            return (new TaskDependenciesCollection($collection_name))
                ->setTask($task)
                ->setWhosAsking($user);
        } else {
            throw new InvalidParamError('collection_name', $collection_name, 'Task ID expected in collection name');
        }
    }

    private static function prepareProjectDependenciesCollection(ModelCollection &$collection, $collection_name, $user)
    {
        $bits = explode('_', $collection_name);

        /** @var Project $project */
        if ($project = DataObjectPool::get(Project::class, array_pop($bits))) {
            if ($user instanceof User && $user->isClient()) {
                $visible_task_ids = DB::executeFirstColumn(
                    'SELECT id FROM tasks WHERE project_id = ? AND is_trashed = ? AND is_hidden_from_clients = ?',
                    $project->getId(),
                    false,
                    false
                );

                $collection->setConditions(
                    'parent_id IN (?) AND child_id IN (?)',
                    $visible_task_ids,
                    $visible_task_ids
                );
            } else {
                $task_ids = DB::executeFirstColumn(
                    'SELECT id FROM tasks WHERE project_id = ? AND is_trashed = ?',
                    $project->getId(),
                    false
                );

                $collection->setConditions(
                    'parent_id IN (?) OR child_id IN (?)',
                    $task_ids,
                    $task_ids
                );
            }

            return $collection;
        } else {
            throw new InvalidParamError('collection_name', $collection_name, 'Project ID expected in collection name');
        }
    }
}
