<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Invoicing\Utils\RecurringInvoicesDispatcher;

use ActiveCollab\Foundation\Notifications\NotificationInterface;
use ActiveCollab\Module\Invoicing\Utils\InvoiceNumberSuggester\InvoiceNumberSuggesterInterface;
use ActiveCollab\Module\Invoicing\Utils\InvoicePreSendChecker\InvoicePreSendCheckerInterface;
use ActiveCollab\Module\Invoicing\Utils\RecurringProfilesToTriggerResolver\RecurringProfilesToTriggerResolverInterface;
use ActiveCollab\Module\System\Events\Maintenance\DailyMaintenanceEvent;
use Angie\Notifications\NotificationsInterface;
use DateValue;
use DraftInvoiceCreatedViaRecurringProfileNotification;
use Invoice;
use InvoiceGeneratedViaRecurringProfileNotification;
use RecurringProfile;

class RecurringInvoicesDispatcher implements RecurringInvoicesDispatcherInterface
{
    private RecurringProfilesToTriggerResolverInterface $profiles_to_trigger_resolver;
    private InvoiceNumberSuggesterInterface $invoice_number_suggester;
    private InvoicePreSendCheckerInterface $invoice_pre_send_checker;
    private NotificationsInterface $notifications;

    public function __construct(
        RecurringProfilesToTriggerResolverInterface $profiles_to_trigger_resolver,
        InvoiceNumberSuggesterInterface $invoice_number_suggester,
        InvoicePreSendCheckerInterface $invoice_pre_send_checker,
        NotificationsInterface $notifications
    )
    {
        $this->profiles_to_trigger_resolver = $profiles_to_trigger_resolver;
        $this->invoice_number_suggester = $invoice_number_suggester;
        $this->invoice_pre_send_checker = $invoice_pre_send_checker;
        $this->notifications = $notifications;
    }

    public function trigger(DateValue $day): array
    {
        $invoices = [];

        foreach ($this->profiles_to_trigger_resolver->getProfilesToTrigger() as $recurring_profile) {
            $invoice = self::processProfile($recurring_profile, $day);

            if ($invoice instanceof Invoice) {
                $invoices[] = $invoice;
            }
        }

        return $invoices;
    }

    private function processProfile(RecurringProfile $recurring_profile, DateValue $date): ?Invoice
    {
        if ($recurring_profile->shouldSendOn($date)) {
            $invoice = $recurring_profile->createInvoice(
                $this->invoice_number_suggester->suggest(),
                null,
                null,
                [
                    'trigger_date' => $date,
                ]
            );

            $safe_to_send = $this->invoice_pre_send_checker->isItSafeToIssueRecurringInvoice(
                $recurring_profile,
                $recurring_profile->getRecipientInstances()
            );

            if ($recurring_profile->getAutoIssue() && $safe_to_send) {
                $invoice->send(
                    $recurring_profile->getCreatedBy(),
                    $recurring_profile->getRecipientInstances(),
                    $recurring_profile->getEmailSubject(),
                    $recurring_profile->getEmailBody()
                );

                $this
                    ->getInvoiceNotification($invoice)
                    ->setInvoice($invoice)
                    ->setProfile($recurring_profile)
                    ->sendToFinancialManagers(true);
            } else {
                $this
                    ->getDraftNotification($invoice)
                    ->setProfile($recurring_profile)
                    ->sendToFinancialManagers(true);
            }

            return $invoice;
        }

        return null;
    }

    /**
     * @return NotificationInterface|InvoiceGeneratedViaRecurringProfileNotification
     */
    private function getInvoiceNotification(Invoice $invoice): NotificationInterface
    {
        return $this->notifications->notifyAbout('invoicing/invoice_generated_via_recurring_profile', $invoice);
    }

    /**
     * @return NotificationInterface|DraftInvoiceCreatedViaRecurringProfileNotification
     */
    private function getDraftNotification(Invoice $invoice): NotificationInterface
    {
        return $this->notifications->notifyAbout(
            'invoicing/draft_invoice_created_via_recurring_profile',
            $invoice
        );
    }

    public function __invoke(DailyMaintenanceEvent $event)
    {
        $this->trigger($event->getDay());
    }
}
