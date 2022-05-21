<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\Invoicing\Utils\InvoiceNumberSuggester\InvoiceNumberSuggesterInterface;
use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\FileDownload\FileDownload;

AngieApplication::useController('auth_required', SystemModule::NAME);

class InvoicesController extends AuthRequiredController
{
    /**
     * Selected invoice.
     *
     * @var Invoice
     */
    protected $active_invoice;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if ($user instanceof User && $user->isFinancialManager()) {
            $this->active_invoice = DataObjectPool::get('Invoice', $request->getId('invoice_id'));

            if (empty($this->active_invoice)) {
                $this->active_invoice = new Invoice();
            }
        } else {
            return Response::NOT_FOUND;
        }
    }

    /**
     * List active invoices.
     *
     * @return ModelCollection
     */
    public function index(Request $request, User $user)
    {
        return Invoices::prepareCollection('active_invoices', $user);
    }

    /**
     * List all archived (paid or canceled) invoices.
     *
     * @return ModelCollection
     */
    public function archive(Request $request, User $user)
    {
        return Invoices::prepareCollection('archived_invoices_page_' . $request->getPage(), $user);
    }

    /**
     * Show private notes for active invoices.
     *
     * @return array
     */
    public function private_notes()
    {
        return Invoices::getPrivateNotes();
    }

    /**
     * Show single invoice.
     *
     * @return int|Invoice
     */
    public function view(Request $request, User $user)
    {
        return $this->active_invoice->isLoaded() ? AccessLogs::logAccess($this->active_invoice, $user) : Response::NOT_FOUND;
    }

    /**
     * Create a new invoice.
     *
     * @return DataObject|Invoice|int
     */
    public function add(Request $request, User $user)
    {
        return Invoices::canAdd($user)
            ? Invoices::create($this->processParametersForAddAction($request, $user))
            : Response::NOT_FOUND;
    }

    /**
     * Process parameters for add action.
     *
     * @return array
     */
    private function processParametersForAddAction(Request $request, User $user)
    {
        $post = $request->post();

        if (empty($post['items']) && !empty($post['items_from_tracked_data'])) {
            $post['items'] = $this->previewItemsFromTrackingFilterSettings($post['items_from_tracked_data'], $user);
            unset($post['items_from_tracked_data']);
        }

        return $post;
    }

    /**
     * @return int|TrackingFilter
     */
    public function preview_items(Request $request, User $user)
    {
        if (Invoices::canAdd($user)) {
            $get = $request->get();

            if (isset($get['project_ids']) && strpos($get['project_ids'], ',') !== false) {
                $get['project_ids'] = explode(',', $get['project_ids']);
            }

            return $this->previewItemsFromTrackingFilterSettings($get, $user);
        }

        return Response::NOT_FOUND;
    }

    private function previewItemsFromTrackingFilterSettings(array $settings, User $user): array
    {
        $project_ids = isset($settings['project_ids']) && $settings['project_ids'] ? (array) $settings['project_ids'] : null;
        $include_time_records = isset($settings['include_time_records']) && intval($settings['include_time_records']);
        $include_expenses = isset($settings['include_expenses']) && intval($settings['include_expenses']);
        $include_non_billable_records = isset($settings['include_non_billable_records']) && $settings['include_non_billable_records'];
        $date_filter_from = isset($settings['date_filter_from']) && $settings['date_filter_from'] ? DateValue::makeFromString($settings['date_filter_from']) : null;
        $date_filter_to = isset($settings['date_filter_to']) && $settings['date_filter_to'] ? DateValue::makeFromString($settings['date_filter_to']) : null;

        $report = new TrackingFilter();
        $report->filterByProjects($project_ids);

        if ($include_time_records && $include_expenses) {
            $report->setTypeFilter(TrackingFilter::TYPE_FILTER_ANY);
        } elseif ($include_time_records) {
            $report->setTypeFilter(TrackingFilter::TYPE_FILTER_TIME);
        } elseif ($include_expenses) {
            $report->setTypeFilter(TrackingFilter::TYPE_FILTER_EXPENSES);
        } else {
            throw new DataFilterConditionsError('type', null, null, 'Include time records or expenses');
        }

        if ($include_non_billable_records) {
            $report->setBillableStatusFilter(TrackingFilter::BILLABLE_FILTER_BILLABLE);
        } else {
            $report->setBillableStatusFilter(TrackingFilter::BILLABLE_FILTER_ALL); // @TODO
        }

        if ($date_filter_from && $date_filter_to) {
            $report->trackedInRange($date_filter_from, $date_filter_to);
        }

        try {
            $result = $report->previewInvoiceItems([
                'sum_by' => isset($settings['sum_by']) && $settings['sum_by'] ? $settings['sum_by'] : Invoice::INVOICE_SETTINGS_KEEP_AS_SEPARATE,
                'first_tax_rate_id' => isset($settings['first_tax_rate_id']) ? $settings['first_tax_rate_id'] : null,
                'second_tax_rate_id' => isset($settings['second_tax_rate_id']) ? $settings['second_tax_rate_id'] : null,
            ], $user);
        } catch (DataFilterConditionsError $e) {
            $result = [];
        }

        return empty($result) ? [] : $result;
    }

    /**
     * Update an invoice.
     *
     * @return bool|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_invoice->isLoaded() && $this->active_invoice->canEdit($user) ? Invoices::update($this->active_invoice, $request->put()) : Response::NOT_FOUND;
    }

    /**
     * Issue the invoice.
     *
     * @return Invoice|int
     */
    public function send(Request $request, User $user)
    {
        if ($this->active_invoice->isLoaded()) {
            /** @var array $recipients */
            [$recipients, $subject, $message] = $this->processParametersForSendAction($request);

            if (empty($recipients)) {
                return Response::BAD_REQUEST;
            }

            if (AngieApplication::isOnDemand()) {
                $filtered_invoice = OnDemand::filterInvoiceRecipients($this->active_invoice, $recipients);

                if ($filtered_invoice instanceof IInvoice) {
                    return $filtered_invoice;
                }
            }

            return $this->active_invoice->send($user, $recipients, $subject, $message);
        }

        return Response::NOT_FOUND;
    }

    /**
     * Process PUT parameters for send() action.
     *
     * @return array
     */
    private function processParametersForSendAction(Request $request)
    {
        $put = $request->put();

        $recipients = [];

        if (isset($put['recipients'])) {
            if (is_string($put['recipients'])) {
                $recipients = Users::findByAddressList($put['recipients']);
            } elseif (is_foreachable($put['recipients'])) {
                foreach ($put['recipients'] as $email) {
                    [$name, $email] = email_split($email);

                    if (is_valid_email($email)) {
                        $recipient = Users::findByEmail($email, true);
                        if ($recipient instanceof User) {
                            $recipients[] = $recipient;
                        } else {
                            $recipients[] = new AnonymousUser(null, $email);
                        }
                    }
                }
            }
        }

        $subject = isset($put['subject']) && $put['subject'] ? trim($put['subject']) : null;
        $message = isset($put['message']) && $put['message'] ? trim($put['message']) : null;

        return [$recipients, $subject, $message];
    }

    /**
     * Export invoice to PDF.
     *
     * @return FileDownload|int
     */
    public function export(Request $request, User $user)
    {
        if ($this->active_invoice->isLoaded() && $this->active_invoice->canView($user)) {
            return new FileDownload($this->active_invoice->exportToFile(), 'application/pdf', Invoices::getInvoicePdfName($this->active_invoice));
        }

        return Response::NOT_FOUND;
    }

    /**
     * Duplicate selected invoice.
     *
     * @return int|string
     */
    public function duplicate(Request $request, User $user)
    {
        if ($this->active_invoice->isLoaded() && Invoices::canAdd($user)) {
            return $this->active_invoice->duplicate($request->post('number'));
        }

        return Response::NOT_FOUND;
    }

    /**
     * Mark this invoice as canceled.
     *
     * @return Invoice|int
     */
    public function cancel(Request $request, User $user)
    {
        return $this->active_invoice->isLoaded() && $this->active_invoice->canEdit($user) ? $this->active_invoice->markAsCanceled($user) : Response::NOT_FOUND;
    }

    /**
     * Release related invoice records.
     *
     * @return int|Invoice
     */
    public function release_related_records(Request $request, User $user)
    {
        if ($this->active_invoice->isLoaded() && $this->active_invoice->canEdit($user)) {
            $this->active_invoice->releaseRelatedRecords();

            return $this->active_invoice;
        }

        return Response::NOT_FOUND;
    }

    /**
     * Delete an invoice.
     *
     * @return bool|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_invoice->isLoaded() && $this->active_invoice->canDelete($user)
            ? Invoices::scrap($this->active_invoice)
            : Response::NOT_FOUND;
    }

    public function suggest_number(): array
    {
        return [
            'number' => AngieApplication::getContainer()
                ->get(InvoiceNumberSuggesterInterface::class)
                    ->suggest(),
        ];
    }

    /**
     * Mark as sent.
     *
     * @return Invoice|int
     */
    public function mark_as_sent(Request $request, User $user)
    {
        if ($this->active_invoice->isLoaded() && $this->active_invoice->canEdit($user)) {
            $this->active_invoice->setSentOn(DateTimeValue::now());
            $this->active_invoice->save();

            return $this->active_invoice;
        }

        return Response::NOT_FOUND;
    }

    /**
     * Mark as paid, only if invoice total is 0.
     *
     * @return Invoice|int
     */
    public function mark_zero_invoice_as_paid(Request $request, User $user)
    {
        if (!$this->active_invoice->isLoaded() || !$this->active_invoice->canEdit($user)) {
            return Response::NOT_FOUND;
        }

        if ($this->active_invoice->getStatus() == Invoice::PAID || $this->active_invoice->getStatus() == Invoice::CANCELED) {
            return Response::BAD_REQUEST;
        }

        if ($this->active_invoice->getTotal()) {
            return Response::BAD_REQUEST;
        }

        $this->active_invoice->markAsPaid($user, DateTimeValue::now());
        $this->active_invoice->save();

        return $this->active_invoice;
    }

    /**
     * @return ModelCollection
     */
    public function projects_invoicing_data(Request $request, User $user)
    {
        return Projects::prepareCollection('projects_invoicing_data', $user);
    }
}
