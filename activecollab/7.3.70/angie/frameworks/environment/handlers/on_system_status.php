<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Load system status of environment services.
 *
 * @package angie.framework.environment
 * @subpackage handlers
 */

/**
 * @param array $status
 */
function environment_handle_on_system_status(array &$status)
{
    $status['cron_is_ok'] = false;
    $status['cron_errors'] = [];

    /** @var CronIntegration $integration */
    if ($integration = Integrations::findFirstByType('CronIntegration')) {
        $status['cron_is_ok'] = $integration->isOk($status['cron_errors']);
    }

    $status['search_is_ok'] = false;
    $status['search_errors'] = [];

    $last_search_job_failed = DB::executeFirstCell('SELECT failed_at FROM jobs_queue_failed WHERE type LIKE ? ORDER BY failed_at DESC', 'ActiveCollab\\\\ActiveCollabJobs\\\\Jobs\\\\Search\\\\%');

    if (!is_null($last_search_job_failed) && strtotime($last_search_job_failed) > time() - 86400) {
        $status['search_errors'][] = lang('There has been search related errors in past 24 hours');
    }

    /** @var SearchIntegration $integration */
    if ($integration = Integrations::findFirstByType('SearchIntegration')) {
        $status['search_is_ok'] = $integration->isOk($status['search_errors']);
    }

    $status['license_key'] = AngieApplication::getLicenseKey();
    $status['support_renewal_url'] = AngieApplication::autoUpgrade()->getRenewSupportUrl();
    $status['support_expires_on'] = AngieApplication::autoUpgrade()->getSupportSubscriptionExpiresOn();

    $status['current_version'] = AngieApplication::getVersion();
    $status['latest_version'] = AngieApplication::autoUpgrade()->getLatestStableVersion();
    $status['latest_available_version'] = AngieApplication::autoUpgrade()->getLatestAvailableVersion();
    $status['new_version_available'] = $status['current_version'] != $status['latest_available_version'];
    $status['release_notes'] = AngieApplication::autoUpgrade()->getReleaseNotes();
}
