<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\DependencyChainsManager;

use ActiveCollab\Module\Tasks\Utils\DirectAcyclicGraphFactory\DirectAcyclicGraphFactoryInterface;
use ActiveCollab\Module\Tasks\Utils\TaskDependenciesResolver\TaskDependenciesResolverInterface;
use DateValue;
use Task;

class DependencyChainsManager implements DependencyChainsManagerInterface, TaskDependencyChainsSorterInterface
{
    private $task_dependency_resolver;
    private $direct_acyclic_graph_factory;
    private $objects_pool_resolver;
    private $object_pool_resolver;

    public function __construct(
        TaskDependenciesResolverInterface $resolver,
        DirectAcyclicGraphFactoryInterface $direct_acyclic_graph_factory,
        callable $objects_pool_resolver,
        callable $object_pool_resolver
    )
    {
        $this->task_dependency_resolver = $resolver;
        $this->direct_acyclic_graph_factory = $direct_acyclic_graph_factory;
        $this->objects_pool_resolver = $objects_pool_resolver;
        $this->object_pool_resolver = $object_pool_resolver;
    }

    public function getParentToChildChains(Task $task, bool $between_scheduled = true): array
    {
        return $this->createChains($task, false, $between_scheduled);
    }

    public function getChildToParentChains(Task $task, bool $between_scheduled = true): array
    {
        return $this->createChains($task, true, $between_scheduled);
    }

    private function createChains(Task $task, bool $child_to_parent = false, bool $between_scheduled = true): array
    {
        $project_dependencies = $this->task_dependency_resolver->getProjectDependencies($task->getProjectId());

        if ($between_scheduled) {
            $this->prepareTasksFromProjectDependencies($project_dependencies);
        }

        $tasks_data = $this->direct_acyclic_graph_factory->createStructure($project_dependencies, $child_to_parent);

        return count($tasks_data) ? $this->dfs($tasks_data, [$task->getId()], $between_scheduled) : [];
    }

    private function dfs(array $tasks_data, array $path, bool $between_scheduled, $paths = []): array
    {
        $task_id = $path[count($path) - 1];

        if (array_key_exists($task_id, $tasks_data)) {
            foreach ($tasks_data[$task_id] as $value) {
                $new_chain = array_merge($path, [$value]);

                if ($between_scheduled) {
                    /** @var Task $task */
                    $task = call_user_func($this->object_pool_resolver, Task::class, $value);

                    if ($task->getDueOn()) {
                        array_push($paths, $new_chain);
                    }
                }

                $paths = $this->dfs($tasks_data, $new_chain, $between_scheduled, $paths);
            }
        } elseif (!$between_scheduled) {
            array_push($paths, $path);
        }

        return $paths;
    }

    private function prepareTasksFromProjectDependencies(array $project_dependencies): void
    {
        $ids = [];

        foreach ($project_dependencies as $dependency) {
            $ids[] = $dependency['parent_id'];
            $ids[] = $dependency['child_id'];
        }

        call_user_func($this->objects_pool_resolver, Task::class, array_unique($ids));
    }

    public function sortChains(array $chains): array
    {
        $result = [];

        if (!empty($chains)) {
            $map = [];
            $ids = array_unique(call_user_func_array('array_merge', $chains));

            // preload tasks
            call_user_func($this->objects_pool_resolver, Task::class, $ids);

            foreach ($chains as $key => $chain) {
                $first = call_user_func($this->object_pool_resolver, Task::class, reset($chain));
                $last = call_user_func($this->object_pool_resolver, Task::class, end($chain));

                $due_on = $first->getDueOn() instanceof DateValue ? $first->getDueOn()->getTimestamp() : 0;
                $start_on = $last->getStartOn() instanceof DateValue ? $last->getStartOn()->getTimestamp() : 0;

                $map[] = ['key' => $key, 'start_on' => $start_on, 'due_on' => $due_on];
            }

            usort($map, function ($a, $b) {
                if ($a['start_on'] == $b['start_on']) {
                    return 0;
                }

                return $a['start_on'] < $b['start_on'] ? -1 : 1;
            });

            usort($map, function ($a, $b) {
                if ($a['due_on'] == $b['due_on']) {
                    return 0;
                }

                return $a['due_on'] > $b['due_on'] ? -1 : 1;
            });

            foreach ($map as $value) {
                $result[] = $chains[$value['key']];
            }
        }

        return $result;
    }
}
