<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Utils;

use ActiveCollab\ActiveCollabJobs\Jobs\Instance\BudgetNotificationMaintenanceJob;
use ActiveCollab\ActiveCollabJobs\Jobs\Instance\Job;
use ActiveCollab\Foundation\App\AccountId\AccountIdResolverInterface;
use ActiveCollab\JobsQueue\JobsDispatcherInterface;
use ActiveCollab\Module\System\SystemModule;
use Angie\Utils\OnDemandStatus\OnDemandStatusInterface;
use DateTimeValue;

class BudgetNotificationsMaintenance implements BudgetNotificationsMaintenanceInterface
{
    private BudgetNotificationsManagerInterface $manager;
    private JobsDispatcherInterface $jobs_dispatcher;
    private AccountIdResolverInterface $account_id_resolver;
    private OnDemandStatusInterface $on_demand_status;

    public function __construct(
        BudgetNotificationsManagerInterface $manager,
        JobsDispatcherInterface $jobs_dispatcher,
        AccountIdResolverInterface $account_id_resolver,
        OnDemandStatusInterface $on_demand_status
    )
    {
        $this->manager = $manager;
        $this->jobs_dispatcher = $jobs_dispatcher;
        $this->account_id_resolver = $account_id_resolver;
        $this->on_demand_status = $on_demand_status;
    }

    public function run(): void
    {
        if (count($this->manager->getProjectsIds()) > 0) {
            $this->jobs_dispatcher->dispatch(
                new BudgetNotificationMaintenanceJob(
                    [
                        'priority' => Job::HAS_HIGHEST_PRIORITY,
                        'instance_id' => $this->account_id_resolver->getAccountId(),
                        'instance_type' => Job::FEATHER,
                        'date' => DateTimeValue::now()->format('Y-m-d'),
                        'attempts' => 3,
                        'ondemand' => $this->on_demand_status->isOnDemand(),
                        'tasks_path' => ENVIRONMENT_PATH . '/tasks',
                    ]
                ),
                SystemModule::MAINTENANCE_JOBS_QUEUE_CHANNEL
            );
        }
    }
}
