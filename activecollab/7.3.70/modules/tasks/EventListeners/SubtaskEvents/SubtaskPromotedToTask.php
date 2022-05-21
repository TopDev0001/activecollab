<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\EventListeners\SubtaskEvents;

use ActiveCollab\Module\Tasks\Events\Subtask\SubtaskPromotedToTaskEventInterface;
use ActiveCollab\Module\Tasks\Features\TaskDependenciesFeatureInterface;
use Angie\Features\FeatureFactoryInterface;

class SubtaskPromotedToTask
{
    private FeatureFactoryInterface $feature_factory;
    private $task_dependency_factory;

    public function __construct(
        FeatureFactoryInterface $feature_factory,
        callable $task_dependency_factory
    )
    {
        $this->feature_factory = $feature_factory;
        $this->task_dependency_factory = $task_dependency_factory;
    }

    public function __invoke(SubtaskPromotedToTaskEventInterface $subtask_event)
    {
        if ($this->feature_factory->makeFeature(TaskDependenciesFeatureInterface::NAME)->isEnabled()) {
            call_user_func(
                $this->task_dependency_factory,
                $subtask_event->getToTask(),
                $subtask_event->getFromTask(),
                $subtask_event->getUser()
            );
        }
    }
}
