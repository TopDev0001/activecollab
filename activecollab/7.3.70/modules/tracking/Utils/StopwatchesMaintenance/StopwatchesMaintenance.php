<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Utils\StopwatchesMaintenance;

use ActiveCollab\ActiveCollabJobs\Jobs\Instance\Job;
use ActiveCollab\ActiveCollabJobs\Jobs\Instance\StopwatchMaintenanceJob;
use ActiveCollab\CurrentTimestamp\CurrentTimestampInterface;
use ActiveCollab\Foundation\App\AccountId\AccountIdResolverInterface;
use ActiveCollab\JobsQueue\JobsDispatcherInterface;
use ActiveCollab\Module\System\SystemModule;
use ActiveCollab\Module\Tracking\Services\StopwatchServiceInterface;
use ActiveCollab\Module\Tracking\Utils\StopwatchesMaintenance\ShouldRunResolver\ShouldRunResolverInterface;
use ActiveCollab\Module\Tracking\Utils\StopwatchManagerInterface;
use Angie\Utils\OnDemandStatus\OnDemandStatusInterface;
use DateTimeValue;

class StopwatchesMaintenance implements StopwatchesMaintenanceInterface
{
    private StopwatchManagerInterface $stopwatch_manager;
    private ShouldRunResolverInterface $should_run_resolver;
    private JobsDispatcherInterface $jobs_dispatcher;
    private AccountIdResolverInterface $account_id_resolver;
    private OnDemandStatusInterface $on_demand_status;
    private CurrentTimestampInterface $current_timestamp;

    public function __construct(
        StopwatchManagerInterface $stopwatch_manager,
        ShouldRunResolverInterface $should_run_resolver,
        JobsDispatcherInterface $jobs_dispatcher,
        AccountIdResolverInterface $account_id_resolver,
        OnDemandStatusInterface $on_demand_status,
        CurrentTimestampInterface $current_timestamp
    )
    {
        $this->stopwatch_manager = $stopwatch_manager;
        $this->jobs_dispatcher = $jobs_dispatcher;
        $this->should_run_resolver = $should_run_resolver;
        $this->account_id_resolver = $account_id_resolver;
        $this->on_demand_status = $on_demand_status;
        $this->current_timestamp = $current_timestamp;
    }

    public function run(): void
    {
        foreach ($this->stopwatches_for_daily as $item) {
            $this->createJob(
                $item,
                $this->calculateDelayForDailyCapacity($item)
            );
        }

        foreach ($this->stopwatches_for_maximum as $item) {
            $this->createJob(
                $item,
                $this->calculateDelayForStopwatchMaximum($item)
            );
        }
    }

    private function createJob(array $item, int $delay)
    {
        $this->jobs_dispatcher->dispatch(
            new StopwatchMaintenanceJob(
                [
                    'priority' => Job::HAS_HIGHEST_PRIORITY,
                    'instance_id' => $this->account_id_resolver->getAccountId(),
                    'instance_type' => Job::FEATHER,
                    'delay' => $delay,
                    'date' => DateTimeValue::now()->format('Y-m-d'),
                    'attempts' => 3,
                    'user_id' => $item['user_id'],
                    'stopwatch_id' => $item['id'],
                    'user_email' => $item['user_email'],
                    'ondemand' => $this->on_demand_status->isOnDemand(),
                ]
            ),
            SystemModule::MAINTENANCE_JOBS_QUEUE_CHANNEL
        );
    }

    public function shouldRun(): bool
    {
        return $this->should_run_resolver->shouldRun(
            $this->stopwatches_for_daily,
            $this->stopwatches_for_maximum,
        );
    }

    public function calculateDelayForDailyCapacity(array $stopwatch): int
    {
        $seconds = $stopwatch['daily_capacity']
            ? ((float) $stopwatch['daily_capacity'] * 3600)
            : ($this->global_daily_capacity * 3600);
        $date = new DateTimeValue($stopwatch['started_on']);
        $date->advance($seconds);
        if ($this->current_timestamp->getCurrentTimestamp() >= $date->getTimestamp()) {
            return 1; //must be positive
        }

        return abs($date->getTimestamp() - $this->current_timestamp->getCurrentTimestamp());
    }

    public function calculateDelayForStopwatchMaximum(array $stopwatch): int
    {
        $date = new DateTimeValue($stopwatch['started_on']);
        $limit = StopwatchServiceInterface::STOPWATCH_MAXIMUM;
        $elapsed = $stopwatch['elapsed'] + ($this->current_timestamp->getCurrentTimestamp() - $date->getTimestamp());
        if ($elapsed > $limit) {
            return 1;
        }
        $delay = abs($limit - $elapsed);

        return $delay > 0 ? $delay : 1;
    }

    private array $stopwatches_for_daily = [];
    private array $stopwatches_for_maximum = [];
    private float $global_daily_capacity = 0.0;

    public function getForMaintenance(): StopwatchesMaintenanceInterface
    {
        $this->stopwatches_for_daily = $this->stopwatch_manager->findStopwatchesForDailyCapacityNotification();
        $this->stopwatches_for_maximum = $this->stopwatch_manager->findStopwatchesForMaximumCapacityNotification();
        $this->global_daily_capacity = $this->stopwatch_manager->getGlobalUserDailyCapacity();

        return $this;
    }

    public function setGlobalDailyCapacity(float $global_daily_capacity): StopwatchesMaintenance
    {
        $this->global_daily_capacity = $global_daily_capacity;

        return $this;
    }
}
