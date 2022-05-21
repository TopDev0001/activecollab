<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\ActiveCollabJobs\Jobs\Smtp\SendMessage;

function email_handle_on_system_status(array &$status)
{
    $status['imap_is_available'] = extension_loaded('imap');

    $status['email_is_ok'] = false;
    $status['email_errors'] = [];

    // check incoming mail
    if ((int) AngieApplication::memories()->get('check_imap_last_run', null, false) < time() - 3600) {
        $status['email_errors'][] = lang('No incoming mail checks has been performed in the past hour');
    }

    // check outgoing mail
    $last_email_sent_on = DB::executeFirstCell('SELECT sent_on FROM email_log ORDER BY sent_on DESC');
    $last_failed_send_job = DB::executeFirstCell('SELECT failed_at FROM jobs_queue_failed WHERE type = ? ORDER BY failed_at DESC', SendMessage::class);

    if (!is_null($last_failed_send_job) && (is_null($last_email_sent_on) || $last_email_sent_on < $last_failed_send_job)) {
        $status['email_errors'][] = lang('No email sent since last sending attempt');
    }

    /** @var EmailIntegration $integration */
    if ($integration = Integrations::findFirstByType('EmailIntegration')) {
        $status['email_is_ok'] = $integration->isOk($status['email_errors']);
    }
}
