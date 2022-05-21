<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Features\AvailabilityFeatureInterface;
use ActiveCollab\Module\System\Features\BudgetingFeatureInterface;
use ActiveCollab\Module\System\Features\CalendarFeatureInterface;
use ActiveCollab\Module\System\Features\ChatFeatureInterface;
use ActiveCollab\Module\System\Features\EstimatesFeatureInterface;
use ActiveCollab\Module\System\Features\HideFromClientsFeatureInterface;
use ActiveCollab\Module\System\Features\InvoicesFeatureInterface;
use ActiveCollab\Module\System\Features\ProfitabilityFeatureInterface;
use ActiveCollab\Module\System\Features\ProjectTemplatesFeatureInterface;
use ActiveCollab\Module\System\Features\TimesheetFeatureInterface;
use ActiveCollab\Module\System\Features\WorkloadFeatureInterface;
use ActiveCollab\Module\Tasks\Features\TaskDependenciesFeatureInterface;
use Angie\Utils\FeatureStatusResolver\FeatureStatusResolverInterface;

function system_handle_on_initial_settings(array &$settings): void
{
    $user = AngieApplication::authentication()->getAuthenticatedUser();

    $integration = Integrations::findFirstByType(
        ClientPlusIntegration::class,
        false
    );

    $settings['client_plus_enabled'] = !empty($integration) && $integration->isInUse();
    $settings['show_onboarding_survey'] = AngieApplication::onboardingSurvey()->shouldShow($user);

    $settings['csrf_validator_cookie_name'] = 'activecollab_' . AngieApplication::getCsrfValidatorCookieName();

    $settings['socket_integration'] = AngieApplication::realTimeIntegrationResolver()->getIntegration();

    $setupWizard = AngieApplication::setupWizard();

    $settings['show_set_password'] = $setupWizard->shouldShowSetPassword($user);

    $settings['show_sample_projects_wizard_step'] = ConfigOptions::getValue('show_sample_projects_wizard_step');
    $settings['project_timeline_export'] = ConfigOptions::getValue('project_timeline_export');

    $settings['default_hide_from_clients'] = ConfigOptions::getValue('default_hide_from_clients');

    if ($setupWizard->shouldShowOnboardingSurvey($user)) {
        $settings['wizard_current_step'] = $setupWizard->getNextWizardStep($user);
    }

    if (AngieApplication::isOnDemand()) {
        $onboarding_survey_cta_stage = AngieApplication::memories()->get(FillOnboardingSurveyNotification::MEMORIES_PREFIX . 'stage', 1);
        $onboarding_survey_cta_dismissed = AngieApplication::memories()->get(FillOnboardingSurveyNotification::MEMORIES_PREFIX . 'dismissed', 0);

        $finally_dismissed = $onboarding_survey_cta_stage === 3 && $onboarding_survey_cta_dismissed === 1;
        $settings['show_features_info'] = $setupWizard->shouldShowFeaturesInfo($user, AngieApplication::accountSettings()->getAccountPlan());

        $settings['should_fill_onboarding_survey'] = $setupWizard->shouldShowOnboardingSurvey($user) && !$finally_dismissed;

        $settings['stripe_api_key'] = AngieApplication::publicStripeApiKey();
    }

    $feature_status_resolver = AngieApplication::getContainer()->get(FeatureStatusResolverInterface::class);

    $features = [
        WorkloadFeatureInterface::NAME,
        HideFromClientsFeatureInterface::NAME,
        ProfitabilityFeatureInterface::NAME,
        AvailabilityFeatureInterface::NAME,
        TimesheetFeatureInterface::NAME,
        InvoicesFeatureInterface::NAME,
        EstimatesFeatureInterface::NAME,
        CalendarFeatureInterface::NAME,
        TaskDependenciesFeatureInterface::NAME,
        BudgetingFeatureInterface::NAME,
        ChatFeatureInterface::NAME,
        ProjectTemplatesFeatureInterface::NAME,
    ];

    foreach ($features as $feature_name) {
        $feature = AngieApplication::featureFactory()->makeFeature($feature_name);
        $settings[$feature_name . '_enabled'] = $feature_status_resolver->isEnabled($feature);
        $settings[$feature_name . '_enabled_lock'] = $feature_status_resolver->isLocked($feature);
    }
}
