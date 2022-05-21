<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Module\Tasks\Utils;

use ActiveCollab\Module\Tasks\Utils\CheckCyclicDependencyResolver\CheckCyclicDependencyResolverInterface;
use ActiveCollab\Module\Tasks\Utils\DatesRescheduleCalculator\DatesRescheduleCalculatorInterface;
use ActiveCollab\Module\Tasks\Utils\DatesRescheduleSimulator\DatesRescheduleSimulatorInterface;
use ActiveCollab\Module\Tasks\Utils\DirectAcyclicGraphFactory\DirectAcyclicGraphFactoryInterface;
use ActiveCollab\Module\Tasks\Utils\TaskDependenciesResolver\TaskDependenciesResolverInterface;
use DateValue;
use Task;

class TaskDependenciesRescheduleSimulator implements DatesRescheduleSimulatorInterface
{
    private $cyclic_dependency_resolver;
    private $task_dependency_resolver;
    private $dates_reschedule_calculator;
    private $direct_acyclic_graph_factory;
    private $objects_pool_resolver;

    public function __construct(
        CheckCyclicDependencyResolverInterface $cyclic_dependency_resolver,
        TaskDependenciesResolverInterface $task_dependency_resolver,
        DatesRescheduleCalculatorInterface $dates_reschedule_calculator,
        DirectAcyclicGraphFactoryInterface $direct_acyclic_graph_factory,
        callable $objects_pool_resolver
    )
    {
        $this->cyclic_dependency_resolver = $cyclic_dependency_resolver;
        $this->task_dependency_resolver = $task_dependency_resolver;
        $this->dates_reschedule_calculator = $dates_reschedule_calculator;
        $this->direct_acyclic_graph_factory = $direct_acyclic_graph_factory;
        $this->objects_pool_resolver = $objects_pool_resolver;
    }

    public function simulateReschedule(Task $task, DateValue $due_on): array
    {
        if (
            !($task->getDueOn() instanceof DateValue)
            || $task->getDueOn()->format('Y-m-d') != $due_on->format('Y-m-d')
        ) {
            $dependencies = $this->task_dependency_resolver->getProjectDependencies($task->getProjectId());

            if ($this->cyclic_dependency_resolver->checkCyclicDependency($dependencies)) {
                throw new \LogicException(lang('Circular dependency detected, action aborted.'));
            }

            $filtered_dependencies = [];

            $this->filterProjectDependencies($task->getId(), $dependencies, $filtered_dependencies);

            if ($filtered_dependencies) {
                $ids = array_map('array_values', $filtered_dependencies);
                $ids = array_unique(call_user_func_array('array_merge', $ids));
                $ids = array_diff($ids, [$task->getId()]);

                $prepared_tasks = [];
                $collection = call_user_func($this->objects_pool_resolver, Task::class, $ids);

                $graph = $this->direct_acyclic_graph_factory->createStructure($filtered_dependencies);

                foreach ($collection as $key => $value) {
                    if (!$value->getIsTrashed()) {
                        $prepared_tasks[$value->getId()] = [
                            'id' => $value->getId(),
                            'name' => $value->getName(),
                            'task_number' => $value->getTaskNumber(),
                            'start_on' => $value->getStartOn(),
                            'due_on' => $value->getDueOn(),
                            'completed_on' => $value->getCompletedOn(),
                        ];
                    } else {
                        unset($graph[$value->getId()]);
                    }
                }

                $filtered_graph = [];

                $this->cleanBrokenChains($task->getId(), $graph, $filtered_graph);

                if (!empty($filtered_graph)) {
                    $unique_ids = [];

                    $unique_ids = array_unique(call_user_func_array('array_merge', array_values($filtered_graph)));

                    foreach ($prepared_tasks as $key => $value) {
                        if (!in_array($value['id'], $unique_ids)) {
                            unset($prepared_tasks[$key]);
                        }
                    }

                    if (!empty($prepared_tasks)) {
                        $old_due_on = $task->getDueOn();

                        if (!($old_due_on instanceof DateValue)) {
                            $old_due_on = $due_on;
                        }

                        $parent = [
                            'id' => $task->getId(),
                            'old' => [
                                'due_on' => clone $old_due_on,
                            ],
                            'new' => [
                                'due_on' => clone $due_on,
                            ],
                        ];

                        return $this->prepareRescheduleSimulation($parent, $filtered_graph, $prepared_tasks);
                    }
                }
            }
        }

        return [];
    }

    private function cleanBrokenChains(int $parent_id, array $graph, array &$filtered): void
    {
        if (isset($graph[$parent_id])) {
            $filtered[$parent_id] = $graph[$parent_id];

            foreach ($graph[$parent_id] as $key => $id) {
                if (isset($graph[$id])) {
                    $this->cleanBrokenChains($id, $graph, $filtered);
                }
            }
        }
    }

    private function filterProjectDependencies(int $parent_id, array $dependencies, array &$filtered): void
    {
        foreach ($dependencies as $key => $value) {
            if ($value['parent_id'] == $parent_id) {
                $filtered[] = $dependencies[$key];

                $this->filterProjectDependencies($value['child_id'], $dependencies, $filtered);
            }
        }
    }

    public function prepareRescheduleSimulation($parent, array $graph, array $tasks): array
    {
        $result = [];

        $days_diff = $parent['old']['due_on']->daysBetween($parent['new']['due_on']);

        $left = $parent['old']['due_on']->getTimestamp() > $parent['new']['due_on']->getTimestamp();

        $days_to_add = $left ? -1 : 1;

        if ($days_diff > 0) {
            $first_date = clone $parent['old']['due_on'];
            $second_date = clone $parent['new']['due_on'];

            if ($left) {
                $first_date = clone $parent['new']['due_on'];
                $second_date = clone $parent['old']['due_on'];
            }

            $days_diff = $this->dates_reschedule_calculator->getDuration($first_date, $second_date);
        }

        foreach ($tasks as $task_id => $task) {
            if (
                $task['start_on'] instanceof DateValue
                && $task['due_on'] instanceof DateValue
            ) {
                $diff = $this->dates_reschedule_calculator->getDuration($task['start_on'], $task['due_on']);

                $new_start_on = clone $task['start_on'];
                $new_due_on = $new_start_on->addDays($diff, false);

                $current_days_diff = 0;

                while ($days_diff > $current_days_diff) {
                    $new_start_on->addDays($days_to_add);
                    $new_due_on->addDays($days_to_add);

                    if (!$new_start_on->isWeekend() && !$new_start_on->isDayOff()) {
                        $current_days_diff++;
                    }
                }

                if ($new_due_on->getTimestamp() > $new_start_on->getTimestamp()) {
                    while ($new_due_on->isWeekend() || $new_due_on->isDayOff()) {
                        $new_due_on->addDays(1);
                    }
                }

                $result[$task_id] = [
                    'id' => $task_id,
                    'name' => $task['name'],
                    'task_number' => $task['task_number'],
                    'old' => [
                        'start_on' => $task['start_on'],
                        'due_on' => $task['due_on'],
                    ],
                    'new' => [
                        'start_on' => $new_start_on,
                        'due_on' => $new_due_on,
                    ],
                    'is_completed' => isset($task['completed_on']) && $task['completed_on'] instanceof DateValue,
                ];
            }
        }

        $this->simulateRescheduleChildrensByParent($parent, $graph, $result);

        // exclude task where the new start_on and
        // the new due_on are not changed
        foreach ($result as $key => $value) {
            $old_start_on = $value['old']['start_on']->getTimestamp();
            $old_due_on = $value['old']['due_on']->getTimestamp();

            $new_start_on = $value['new']['start_on']->getTimestamp();
            $new_due_on = $value['new']['due_on']->getTimestamp();

            if ($old_start_on === $new_start_on && $old_due_on === $new_due_on) {
                unset($result[$key]);
            }
        }

        return array_values($result);
    }

    /**
     * @param array $parent
     */
    private function simulateRescheduleChildrensByParent(
        $parent,
        array $parent_children_map,
        array &$result
    ): void
    {
        if (isset($parent['id']) && isset($parent_children_map[$parent['id']])) {
            $parent_due_on = $parent['new']['due_on'];
            $parent_old_due_on = $parent['old']['due_on'];

            foreach ($parent_children_map[$parent['id']] as $child_id) {
                if (isset($result[$child_id])) {
                    $child_old_start_on = $result[$child_id]['old']['start_on'];
                    $child_old_due_on = $result[$child_id]['old']['due_on'];
                    $child_start_on = $result[$child_id]['new']['start_on'];
                    $child_due_on = $result[$child_id]['new']['due_on'];

                    $old_child_duration = $this->dates_reschedule_calculator->getDuration(
                        $result[$child_id]['old']['start_on'],
                        $result[$child_id]['old']['due_on']
                    );

                    // where child new start_on is less than parent new due_on
                    // move child new start_on to day after parent new due_on and
                    // keep the number of days between child new star_on and child new due_on
                    if ($child_start_on->getTimestamp() <= $parent_due_on->getTimestamp()) {
                        $child_start_on = $this->dates_reschedule_calculator->addDays($parent_due_on, 1);
                        $child_due_on = $this->dates_reschedule_calculator->addDays($child_start_on, $old_child_duration);
                    }

                    // diff between parent old due_on and child old start_on
                    // where only working days are counted
                    $old_diff = 0;

                    if ($parent_old_due_on->getTimestamp() < $child_old_start_on->getTimestamp()) {
                        $old_diff = $this->dates_reschedule_calculator->getWorkingDays(
                            $parent_old_due_on,
                            $child_old_start_on
                        );
                    }

                    // diff between parent new due_on and child new start_on
                    // where only working days are counted
                    $new_diff = 0;

                    if ($parent_due_on->getTimestamp() < $child_start_on->getTimestamp()) {
                        $new_diff = $this->dates_reschedule_calculator->getWorkingDays(
                            $parent_due_on,
                            $child_start_on
                        );
                    }

                    // move start_on to first working day and
                    // keep old diff between parent due_on
                    if ($old_diff > $new_diff) {
                        if ($child_start_on->isWeekend() || $child_start_on->isDayOff()) {
                            $new_diff--;
                        }

                        $child_start_on = $this->dates_reschedule_calculator->addDays(
                            $child_start_on,
                            $old_diff - $new_diff
                        );
                        $child_due_on = $this->dates_reschedule_calculator->addDays($child_start_on, $old_child_duration);
                    }

                    // collect new child duration
                    // if old "start on" or "due on" is on non working day, new duration shouldn't exclude days off
                    // otherwise, new duration must exclude days off
                    if ($child_old_start_on->isWeekend() || $child_old_start_on->isDayOff()
                        || $child_old_due_on->isWeekend() || $child_old_due_on->isDayOff()) {
                        $new_child_duration = $child_start_on->daysBetween($child_due_on);
                    } else {
                        $new_child_duration = $this->dates_reschedule_calculator->getDuration(
                            $child_start_on,
                            $child_due_on
                        );
                    }

                    // compare new child duration with old child
                    // duration and increase it when it's less
                    if ($old_child_duration > $new_child_duration) {
                        if ($child_due_on->isWeekend() || $child_due_on->isDayOff()) {
                            $new_child_duration--;
                        }

                        $child_due_on = $this->dates_reschedule_calculator->addDays(
                            $child_due_on,
                            $old_child_duration - $new_child_duration
                        );
                    }

                    $result[$child_id]['new'] = [
                        'start_on' => $child_start_on,
                        'due_on' => $child_due_on,
                    ];

                    $this->simulateRescheduleChildrensByParent($result[$child_id], $parent_children_map, $result);
                }
            }
        }
    }
}
