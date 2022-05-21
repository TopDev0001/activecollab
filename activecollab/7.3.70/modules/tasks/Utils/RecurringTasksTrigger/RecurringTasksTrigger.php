<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\RecurringTasksTrigger;

use ActiveCollab\Logger\LoggerInterface;
use ActiveCollab\Module\System\Utils\Storage\StorageOverusedNotifier\StorageOverusedNotifierInterface;
use Angie\Storage\OveruseResolver\StorageOveruseResolverInterface;
use Angie\Utils\OnDemandStatus\OnDemandStatusInterface;
use DateValue;
use RecurringTask;
use Task;

class RecurringTasksTrigger implements RecurringTasksTriggerInterface
{
    private $recurring_tasks_to_trigger_resolver;
    private OnDemandStatusInterface $on_demand_status_resolver;
    private StorageOveruseResolverInterface $storage_overuse_resolver;
    private StorageOverusedNotifierInterface $storage_overused_notifier;
    private LoggerInterface $logger;

    public function __construct(
        callable $recurring_tasks_to_trigger_resolver,
        OnDemandStatusInterface $on_demand_status_resolver,
        StorageOveruseResolverInterface $storage_overuse_resolver,
        StorageOverusedNotifierInterface $storage_overused_notifier,
        LoggerInterface $logger
    )
    {
        $this->recurring_tasks_to_trigger_resolver = $recurring_tasks_to_trigger_resolver;
        $this->on_demand_status_resolver = $on_demand_status_resolver;
        $this->storage_overuse_resolver = $storage_overuse_resolver;
        $this->storage_overused_notifier = $storage_overused_notifier;
        $this->logger = $logger;
    }

    public function createForDay(DateValue $day): iterable
    {
        $reference = microtime(true);

        $result = [];

        /** @var RecurringTask[] $recurring_tasks */
        $recurring_tasks = call_user_func($this->recurring_tasks_to_trigger_resolver);

        if (is_iterable($recurring_tasks)) {
            foreach ($recurring_tasks as $recurring_task) {
                $task = $this->processRecurringTask($recurring_task, $day);

                if ($task instanceof Task) {
                    $result[] = $task->getId();
                }
            }
        }

        $tasks_created_count = count($result);

        if ($tasks_created_count) {
            $this->logger->info(
                'Recurring tasks created for {day}.',
                [
                    'day' => $day->format('Y-m-d'),
                    'tasks_created' => $tasks_created_count,
                    'exec_time' => round(microtime(true) - $reference, 5),
                ]
            );
        }

        return $result;
    }

    public function processRecurringTask(RecurringTask $recurring_task, DateValue $day): ?Task
    {
        if ($recurring_task->shouldSendOn($day)) {
            if ($recurring_task->countAttachments(false) && $this->isDiskFull()) {
                $this->storage_overused_notifier->notifyAdministrators();

                $this->logger->notice(
                    'Recurring task #{recurring_task_id} skipped due to storage overuse',
                    [
                        'recurring_task_id' => $recurring_task->getId(),
                        'recurring_interval' => $recurring_task->getRepeatFrequency(),
                        'date' => $day->format('Y-m-d'),
                    ]
                );

                return null;
            }

            return $recurring_task->createTask($day);
        }

        $this->logger->info(
            'Recurring task #{recurring_task_id} ({recurring_interval}) should not be sent on {date}',
            [
                'recurring_task_id' => $recurring_task->getId(),
                'recurring_interval' => $recurring_task->getRepeatFrequency(),
                'date' => $day->format('Y-m-d'),
            ]
        );

        return null;
    }

    private function isDiskFull(): bool
    {
        return $this->on_demand_status_resolver->isOnDemand()
            && $this->storage_overuse_resolver->isDiskFull(true);
    }
}
