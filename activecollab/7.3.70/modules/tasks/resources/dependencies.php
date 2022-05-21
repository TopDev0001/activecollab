<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\Invoicing\Utils\VariableProcessor\Factory\VariableProcessorFactoryInterface;
use ActiveCollab\Module\System\Utils\Recurrence\Interval\Factory\RecurrenceIntervalFactory;
use ActiveCollab\Module\System\Utils\Recurrence\Interval\Factory\RecurrenceIntervalFactoryInterface;
use ActiveCollab\Module\System\Utils\Storage\StorageOverusedNotifier\StorageOverusedNotifierInterface;
use ActiveCollab\Module\Tasks\Utils\GhostTasks\Resolver\GhostTasksResolver;
use ActiveCollab\Module\Tasks\Utils\GhostTasks\Resolver\GhostTasksResolverInterface;
use ActiveCollab\Module\Tasks\Utils\RecurringTasksTrigger\RecurringTasksTrigger;
use ActiveCollab\Module\Tasks\Utils\RecurringTasksTrigger\RecurringTasksTriggerInterface;
use ActiveCollab\Module\Tasks\Utils\TaskFromRecurringTaskProducer\TaskFromRecurringTaskProducer;
use ActiveCollab\Module\Tasks\Utils\TaskFromRecurringTaskProducer\TaskFromRecurringTaskProducerInterface;
use Angie\Notifications\NotificationsInterface;
use Angie\Storage\OveruseResolver\StorageOveruseResolverInterface;
use function DI\get;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return [
    RecurrenceIntervalFactoryInterface::class => get(RecurrenceIntervalFactory::class),

    // @TODO: Remove calls to AngieApplication to resolve dependencies.
    TaskFromRecurringTaskProducerInterface::class => function (ContainerInterface $c) {
        return new TaskFromRecurringTaskProducer(
            $c->get(VariableProcessorFactoryInterface::class),
            $c->get(NotificationsInterface::class),
            $c->get(LoggerInterface::class),
        );
    },
    GhostTasksResolverInterface::class => get(GhostTasksResolver::class),

    // @TODO: Remove calls to AngieApplication to resolve dependencies.
    RecurringTasksTriggerInterface::class => function (ContainerInterface $container) {
        return new RecurringTasksTrigger(
            function () {
                return RecurringTasks::getRecurringTasksToTrigger();
            },
            AngieApplication::onDemandStatus(),
            $container->get(StorageOveruseResolverInterface::class),
            $container->get(StorageOverusedNotifierInterface::class),
            AngieApplication::log()
        );
    },
];
