<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\ScheduleDependenciesChainsService;

use ActiveCollab\Module\Tasks\Utils\DependencyChainsManager\DependencyChainsManagerInterface;
use ActiveCollab\Module\Tasks\Utils\TaskDateRescheduler\TaskDatesInjectorInterface;
use DateValue;
use LogicException;
use Task;

class ScheduleDependenciesChainsService implements ScheduleDependenciesChainsServiceInterface
{
    private $dependency_chains_manager;
    private $task_dates_injector;
    private $object_pool_resolver;

    public function __construct(
        DependencyChainsManagerInterface $dependency_chains_manager,
        TaskDatesInjectorInterface $task_dates_injector,
        callable $object_pool_resolver
    )
    {
        $this->dependency_chains_manager = $dependency_chains_manager;
        $this->task_dates_injector = $task_dates_injector;
        $this->object_pool_resolver = $object_pool_resolver;
    }

    private $task = null;

    private function setInitialTask(Task $task): void
    {
        $this->task = $task;
    }

    public function makeSimulation(Task $task1, ?Task $task2 = null): array
    {
        if (!$task2) {
            $this->setInitialTask($task1);

            return $this->reschedulChainsForOneTask($task1);
        } elseif ($task1->getDueOn() && $task2->getDueOn()) {
            return []; // do nothing when both task1 and task2 have dates
        } elseif (!$task1->getDueOn() && !$task2->getDueOn()) {
            return $this->reschedulChainsForTwoTasks($task1, $task2);
        } else {
            if (!$task1->getDueOn()) {
                return $this->reschedulOnlyChildToParentChains($task2);
            } else {
                return $this->reschedulOnlyParentToChildChains($task1);
            }
        }
    }

    private function reschedulChainsForTwoTasks(Task $parent_task, Task $child_task): array
    {
        $simulation = [];

        $child_to_parent_chains = $this->dependency_chains_manager->getChildToParentChains($parent_task);
        $parent_to_child__chains = $this->dependency_chains_manager->getParentToChildChains($child_task);

        if (empty($child_to_parent_chains) || empty($parent_to_child__chains)) {
            return $simulation;
        }

        $child_to_parent_chains = $this->dependency_chains_manager->sortChains(
            $this->invertMatrixChain($child_to_parent_chains)
        );
        $parent_to_child__chains = $this->dependency_chains_manager->sortChains($parent_to_child__chains);

        $first_child_to_parent_chain = array_shift($child_to_parent_chains);
        $first_parent_to_child_chain = array_shift($parent_to_child__chains);

        $priority_chain = array_merge(
            $first_child_to_parent_chain,
            $first_parent_to_child_chain
        );

        $this->prepareValuesFromChain($priority_chain, $simulation);

        $this->fillSimulationFromMatrixChain($child_to_parent_chains, $simulation);
        $this->fillSimulationFromMatrixChain($child_to_parent_chains, $simulation);

        return array_values($simulation);
    }

    private function reschedulChainsForOneTask(Task $task): array
    {
        if (!$task->getStartOn() || !$task->getDueOn()) {
            throw new LogicException('Task must have start on and due on for reschedule its chains.');
        }

        $simulation = [];
        $should_invert_chains = false;
        $matrix_chain = $this->dependency_chains_manager->getParentToChildChains($task);

        if (empty($matrix_chain)) {
            $should_invert_chains = true;
            $matrix_chain = $this->dependency_chains_manager->getChildToParentChains($task);
        }

        if (!empty($matrix_chain)) {
            if ($should_invert_chains) {
                $matrix_chain = $this->invertMatrixChain($matrix_chain);
            }

            $matrix_chain = $this->dependency_chains_manager->sortChains($matrix_chain);

            $this->fillSimulationFromMatrixChain($matrix_chain, $simulation);
        }

        return array_values($simulation);
    }

    private function reschedulOnlyChildToParentChains(Task $task): array
    {
        $simulation = [];
        $matrix_chain = $this->dependency_chains_manager->getChildToParentChains($task);

        if (count($matrix_chain)) {
            $matrix_chain = $this->dependency_chains_manager->sortChains(
                $this->invertMatrixChain($matrix_chain)
            );

            $this->fillSimulationFromMatrixChain($matrix_chain, $simulation);
        }

        return array_values($simulation);
    }

    private function reschedulOnlyParentToChildChains(Task $task): array
    {
        $simulation = [];
        $matrix_chain = $this->dependency_chains_manager->getParentToChildChains($task);

        if (count($matrix_chain)) {
            $matrix_chain = $this->dependency_chains_manager->sortChains($matrix_chain);

            $this->fillSimulationFromMatrixChain($matrix_chain, $simulation);
        }

        return array_values($simulation);
    }

    private function invertMatrixChain(array $chains): array
    {
        $result = [];

        foreach ($chains as $chain) {
            $result[] = array_reverse($chain);
        }

        return $result;
    }

    private function fillSimulationFromMatrixChain(array $matrix_chain, array &$simulation): void
    {
        foreach ($matrix_chain as $chain) {
            if (count($chain) > 2) {
                $this->prepareValuesFromChain($chain, $simulation);
            }
        }
    }

    private function prepareValuesFromChain(array $chain, array &$simulation): void
    {
        $from = null;
        $to = null;
        $tasks = [];

        foreach ($chain as $key => $id) {
            if ($this->task instanceof Task && $id === $this->task->getId()) {
                $task = $this->task;
            } else {
                /** @var Task $task */
                $task = call_user_func($this->object_pool_resolver, Task::class, $id);
            }

            if ($task->getDueOn()) {
                if (empty($tasks)) {
                    $from = $task->getDueOn();
                } else {
                    $to = $task->getStartOn();
                    break;
                }
            } elseif (!$task->getDueOn() && !array_key_exists($id, $simulation)) {
                $tasks[] = $task;
            } else {
                if (!empty($tasks)) {
                    $to = new DateValue($simulation[$id]['new']['start_on']);
                    break;
                } else {
                    $from = new DateValue($simulation[$id]['new']['due_on']);
                }
            }
        }

        if ($from && $to && count($tasks)) {
            $this->task_dates_injector->injectDates($from, $to, $tasks);

            foreach ($tasks as $task) {
                $simulation[$task->getId()] = [
                    'id' => $task->getId(),
                    'name' => $task->getName(),
                    'task_number' => $task->getTaskNumber(),
                    'old' => [
                        'start_on' => null,
                        'due_on' => null,
                    ],
                    'new' => [
                        'start_on' => $task->getStartOn()->getTimestamp(),
                        'due_on' => $task->getDueOn()->getTimestamp(),
                    ],
                    'is_completed' => $task->isCompleted(),
                ];
            }
        }
    }
}
