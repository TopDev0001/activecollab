<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/*
 * Handle on_initial_settings event handler.
 *
 * @package ActiveCollab.modules.tasks
 * @subpackage handlers
 */

use ActiveCollab\Module\Tasks\Features\AutoRescheduleFeatureInterface;
use ActiveCollab\Module\Tasks\Features\RecurringTasksFeatureInterface;
use ActiveCollab\Module\Tasks\Features\TaskDependenciesFeatureInterface;
use ActiveCollab\Module\Tasks\Features\TaskEstimatesFeatureInterface;
use ActiveCollab\Module\Tasks\Features\TimelineFeatureInterface;

function tasks_handle_on_initial_settings(array &$settings): void
{
    $settings['show_project_id'] = ConfigOptions::getValue('show_project_id');
    $settings['show_task_id'] = ConfigOptions::getValue('show_task_id');
    $settings['show_task_estimates_to_clients'] = ConfigOptions::getValue('show_task_estimates_to_clients');

    $features = [
        TaskEstimatesFeatureInterface::NAME,
        TaskDependenciesFeatureInterface::NAME,
        AutoRescheduleFeatureInterface::NAME,
        TimelineFeatureInterface::NAME,
        RecurringTasksFeatureInterface::NAME,
    ];

    foreach ($features as $feature_name) {
        $feature = AngieApplication::featureFactory()->makeFeature($feature_name);
        $settings[$feature_name . '_enabled'] = $feature->isEnabled();
        $settings[$feature_name . '_enabled_lock'] = $feature->isLocked();
    }
}
