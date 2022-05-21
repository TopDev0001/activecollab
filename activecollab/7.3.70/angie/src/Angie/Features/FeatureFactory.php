<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Features;

use ActiveCollab\EventsDispatcher\EventsDispatcherInterface;
use ActiveCollab\Module\System\Features\AvailabilityFeature;
use ActiveCollab\Module\System\Features\AvailabilityFeatureInterface;
use ActiveCollab\Module\System\Features\BudgetingFeature;
use ActiveCollab\Module\System\Features\BudgetingFeatureInterface;
use ActiveCollab\Module\System\Features\CalendarFeature;
use ActiveCollab\Module\System\Features\CalendarFeatureInterface;
use ActiveCollab\Module\System\Features\ChatFeature;
use ActiveCollab\Module\System\Features\ChatFeatureInterface;
use ActiveCollab\Module\System\Features\EstimatesFeature;
use ActiveCollab\Module\System\Features\EstimatesFeatureInterface;
use ActiveCollab\Module\System\Features\ExpenseTrackingFeature;
use ActiveCollab\Module\System\Features\ExpenseTrackingFeatureInterface;
use ActiveCollab\Module\System\Features\HideFromClientsFeature;
use ActiveCollab\Module\System\Features\HideFromClientsFeatureInterface;
use ActiveCollab\Module\System\Features\InvoicesFeature;
use ActiveCollab\Module\System\Features\InvoicesFeatureInterface;
use ActiveCollab\Module\System\Features\ProfitabilityFeature;
use ActiveCollab\Module\System\Features\ProfitabilityFeatureInterface;
use ActiveCollab\Module\System\Features\ProjectTemplatesFeature;
use ActiveCollab\Module\System\Features\ProjectTemplatesFeatureInterface;
use ActiveCollab\Module\System\Features\SlackIntegrationFeature;
use ActiveCollab\Module\System\Features\SlackIntegrationFeatureInterface;
use ActiveCollab\Module\System\Features\TaskTimeTrackingFeature;
use ActiveCollab\Module\System\Features\TaskTimeTrackingFeatureInterface;
use ActiveCollab\Module\System\Features\TimesheetFeature;
use ActiveCollab\Module\System\Features\TimesheetFeatureInterface;
use ActiveCollab\Module\System\Features\WebhooksIntegrationFeature;
use ActiveCollab\Module\System\Features\WebhooksIntegrationFeatureInterface;
use ActiveCollab\Module\System\Features\WorkloadFeature;
use ActiveCollab\Module\System\Features\WorkloadFeatureInterface;
use ActiveCollab\Module\Tasks\Features\AutoRescheduleFeature;
use ActiveCollab\Module\Tasks\Features\AutoRescheduleFeatureInterface;
use ActiveCollab\Module\Tasks\Features\RecurringTasksFeature;
use ActiveCollab\Module\Tasks\Features\RecurringTasksFeatureInterface;
use ActiveCollab\Module\Tasks\Features\TaskDependenciesFeature;
use ActiveCollab\Module\Tasks\Features\TaskDependenciesFeatureInterface;
use ActiveCollab\Module\Tasks\Features\TaskEstimatesFeature;
use ActiveCollab\Module\Tasks\Features\TaskEstimatesFeatureInterface;
use ActiveCollab\Module\Tasks\Features\TimelineFeature;
use ActiveCollab\Module\Tasks\Features\TimelineFeatureInterface;
use InvalidArgumentException;
use LogicException;

final class FeatureFactory implements FeatureFactoryInterface
{
    private EventsDispatcherInterface $dispatcher;
    private array $known_features = [
        TaskEstimatesFeatureInterface::NAME => TaskEstimatesFeature::class,
        WorkloadFeatureInterface::NAME => WorkloadFeature::class,
        HideFromClientsFeatureInterface::NAME => HideFromClientsFeature::class,
        ProfitabilityFeatureInterface::NAME => ProfitabilityFeature::class,
        AvailabilityFeatureInterface::NAME => AvailabilityFeature::class,
        TimesheetFeatureInterface::NAME => TimesheetFeature::class,
        EstimatesFeatureInterface::NAME => EstimatesFeature::class,
        InvoicesFeatureInterface::NAME => InvoicesFeature::class,
        CalendarFeatureInterface::NAME => CalendarFeature::class,
        TaskDependenciesFeatureInterface::NAME => TaskDependenciesFeature::class,
        TaskTimeTrackingFeatureInterface::NAME => TaskTimeTrackingFeature::class,
        ExpenseTrackingFeatureInterface::NAME => ExpenseTrackingFeature::class,
        WebhooksIntegrationFeatureInterface::NAME => WebhooksIntegrationFeature::class,
        AutoRescheduleFeatureInterface::NAME => AutoRescheduleFeature::class,
        SlackIntegrationFeatureInterface::NAME => SlackIntegrationFeature::class,
        TimelineFeatureInterface::NAME => TimelineFeature::class,
        BudgetingFeatureInterface::NAME => BudgetingFeature::class,
        ChatFeatureInterface::NAME => ChatFeature::class,
        RecurringTasksFeatureInterface::NAME => RecurringTasksFeature::class,
        ProjectTemplatesFeatureInterface::NAME => ProjectTemplatesFeature::class,
    ];

    public function __construct(EventsDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function getKnownFeatureNames(): iterable
    {
        return array_keys($this->known_features);
    }

    public function getKnownFeatures(): iterable
    {
        foreach (array_keys($this->known_features) as $k) {
            yield $this->makeFeature($k);
        }
    }

    public function makeFeature(string $feature_name): FeatureInterface
    {
        if (empty($this->known_features[$feature_name])) {
            throw new InvalidArgumentException(sprintf("Unknown feature '%s'.", $feature_name));
        }

        $feature = new $this->known_features[$feature_name]($this->dispatcher);

        if (!$feature instanceof FeatureInterface) {
            throw new LogicException(sprintf("Class '%s' for '%s' feature is not a proper feature class.", $this->known_features[$feature_name], $feature));
        }

        return $feature;
    }
}
