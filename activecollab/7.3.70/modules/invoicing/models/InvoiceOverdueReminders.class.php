<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Send invoice overdue reminders.
 *
 * @package ActiveCollab.modules.invoicing
 * @subpackage models
 */
final class InvoiceOverdueReminders
{
    /**
     * Find all overdue invoices and send reminders.
     */
    public static function send()
    {
        if (ConfigOptions::getValue('invoice_overdue_reminders_enabled')) {
            if ($overdue_invoices = self::findOverdueInvoices()) {
                foreach ($overdue_invoices as $overdue_invoice) {
                    if (!$overdue_invoice->getIsMuted()) {
                        // Re-send reminder
                        if ($overdue_invoice->getReminderSentOn() instanceof DateTimeValue) {
                            $send_every = ConfigOptions::getValue('invoice_overdue_reminders_send_every');
                            $message = ConfigOptions::getValue('invoice_overdue_reminders_first_message');

                            if (date('Y-m-d', strtotime("+$send_every day", $overdue_invoice->getReminderSentOn()->getTimestamp())) <= DateValue::now()->toMySQL()) {
                                self::sendReminder($overdue_invoice, $message);
                            }

                            // Send escalation reminder
                            if (ConfigOptions::getValue('invoice_overdue_reminders_escalation_enabled')) {
                                $escalations = ConfigOptions::getValue('invoice_overdue_reminders_escalation_messages');
                                if (is_foreachable($escalations)) {
                                    foreach ($escalations as $escalation) {
                                        $send_escalated_after = array_var($escalation, 'send_escalated');
                                        $message = array_var($escalation, 'escalated_message');

                                        if (strtotime("+$send_escalated_after day", $overdue_invoice->getDueOn()->getTimestamp()) == strtotime(DateValue::now()->toMySQL())) {
                                            self::sendReminder($overdue_invoice, $message);
                                        }
                                    }
                                }
                            }

                            // Send first reminder
                        } else {
                            $send_first_after = ConfigOptions::getValue('invoice_overdue_reminders_send_first');
                            $message = ConfigOptions::getValue('invoice_overdue_reminders_first_message');

                            if (date('Y-m-d', strtotime("+$send_first_after day", $overdue_invoice->getDueOn()->getTimestamp())) <= DateValue::now()->toMySQL()) {
                                self::sendReminder($overdue_invoice, $message);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Find overdue invoices.
     *
     * @param  int[]|null $exclude_company_ids
     * @return Invoice[]
     */
    private static function findOverdueInvoices(array $exclude_company_ids = null)
    {
        $conditions = [DB::prepare('closed_on IS NULL AND due_on < ?', DateTimeValue::now())];

        if ($exclude_company_ids && is_foreachable($exclude_company_ids)) {
            $conditions[] = DB::prepare('company_id NOT IN (?)', $exclude_company_ids);
        }

        return Invoices::find(['conditions' => implode(' AND ', $conditions), 'order' => 'due_on DESC']);
    }

    /**
     * Send overdue invoice reminder.
     *
     * @param  string    $message
     * @throws Exception
     */
    private static function sendReminder(Invoice $overdue_invoice, $message)
    {
        try {
            DB::beginWork('Send invoice overdue reminder @ ' . __CLASS__);

            $overdue_invoice->setReminderSentOn(new DateTimeValue());
            $overdue_invoice->save();

            $recipients = $overdue_invoice->getRecipientInstances();

            AngieApplication::notifications()
                ->notifyAbout('invoicing/invoice_reminder', $overdue_invoice)
                ->setReminderMessage($message)
                ->sendToUsers($recipients, true);

            DB::commit('Invoice overdue reminder sent @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to send invoice overdue reminder @ ' . __CLASS__);
            throw $e;
        }
    }
}
