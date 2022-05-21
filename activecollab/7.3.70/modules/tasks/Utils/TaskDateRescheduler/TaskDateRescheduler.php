<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\TaskDateRescheduler;

use ActiveCollab\Module\Tasks\Utils\DatesRescheduleSimulator\DatesRescheduleSimulatorInterface;
use ActiveCollab\Module\Tasks\Utils\ScheduleDependenciesChainsService\ScheduleDependenciesChainsServiceInterface;
use DateValue;
use Task;
use Tasks;
use User;

class TaskDateRescheduler implements TaskDateReschedulerInterface, DatesRescheduleSimulatorInterface
{
    private $schedule_dependencies_chains_service;
    private $task_dependencies_reschedule_simulator;

    public function __construct(
        DatesRescheduleSimulatorInterface $task_dependencies_reschedule_simulator,
        ScheduleDependenciesChainsServiceInterface $schedule_dependencies_chains_service
    )
    {
        $this->task_dependencies_reschedule_simulator = $task_dependencies_reschedule_simulator;
        $this->schedule_dependencies_chains_service = $schedule_dependencies_chains_service;
    }

    public function updateInitialTaskDate(Task $task, DateValue $start_on, DateValue $due_on): Task
    {
        return Tasks::update(
            $task,
            [
                'start_on' => $start_on,
                'due_on' => $due_on,
            ]
        );
    }

    public function isSimulationIdentical(array $old_simulation, array $new_simulation): bool
    {
        $decoded_new_simulation = json_decode(json_encode($new_simulation), true);

        return md5(serialize($old_simulation)) === md5(serialize($decoded_new_simulation));
    }

    public function updateSimulationTaskDates(Task $initial_task, array $new_simulation, User $by): array
    {
        $task_ids = [];
        $task_attributes = [];
        $tasks = [];

        if (is_foreachable($new_simulation)) {
            foreach ($new_simulation as $simulation_task) {
                if (
                    array_key_exists('id', $simulation_task)
                    && array_key_exists('new', $simulation_task)
                ) {
                    if (
                        array_key_exists('start_on', $simulation_task['new'])
                        && array_key_exists('due_on', $simulation_task['new'])
                    ) {
                        array_push($task_attributes, [
                            'id' => $simulation_task['id'],
                            'start_on' => $simulation_task['new']['start_on'],
                            'due_on' => $simulation_task['new']['due_on'],
                        ]);
                        array_push($task_ids, $simulation_task['id']);
                    }
                }
            }

            if (is_foreachable($task_ids) && is_foreachable($task_attributes)) {
                $tasks = Tasks::bulkUpdate($task_ids, $task_attributes, $by);
            }
        }

        array_push($tasks, $initial_task);

        return $tasks;
    }

    public function simulateReschedule(Task $task, DateValue $due_on): array
    {
        if ($task->getDueOn()) {
            return $this->task_dependencies_reschedule_simulator->simulateReschedule($task, $due_on);
        } else {
            $task->setStartOn($due_on);
            $task->setDueOn($due_on);

            return $this->schedule_dependencies_chains_service->makeSimulation($task);
        }
    }
}
