<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * on_protected_config_options event handler.
 *
 * @package ActiveCollab.modules.invoicing
 * @subpackage handlers
 */

/**
 * Handle on_protected_config_options event.
 */
function invoicing_handle_on_protected_config_options()
{
    ConfigOptions::protect([
        'description_format_grouped_by_task',
        'description_format_grouped_by_project',
        'description_format_grouped_by_job_type',
        'description_format_separate_items',
        'first_record_summary_transformation',
        'second_record_summary_transformation',
        'invoice_template',
        'invoicing_default_due',
        'invoice_second_tax_is_enabled',
        'invoice_second_tax_is_compound',
        'invoice_notify_on_payment',
        'invoice_notify_on_cancel',
        'invoice_notify_financial_managers',
        'invoice_notify_financial_manager_ids',
        'invoice_overdue_reminders_enabled',
        'invoice_overdue_reminders_send_first',
        'invoice_overdue_reminders_send_every',
        'invoice_overdue_reminders_escalation_enabled',
        'invoice_overdue_reminders_escalation_messages',
        'invoice_overdue_reminders_dont_send_to',
    ],
    function (User $user) {
        return $user->isOwner();
    }, function (User $user) {
        return $user->isOwner();
    });
}
