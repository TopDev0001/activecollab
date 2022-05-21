<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Foundation\Text\VariableProcessor\VariableProcessorInterface;
use ActiveCollab\Foundation\Urls\Router\RouterInterface;
use ActiveCollab\Module\Tracking\Services\TrackingServiceInterface;
use Angie\Globalization;
use Angie\Search\SearchDocument\SearchDocumentInterface;

class Invoice extends BaseInvoice implements IPayments
{
    // Primary statuses
    public const ISSUED = 'issued';
    public const PAID = 'paid';
    public const CANCELED = 'canceled';
    public const OVERDUE = 'overdue';
    public const SENT = 'sent';
    public const UNSENT = 'unsent';

    // Invoice setting
    public const INVOICE_SETTINGS_SUM_ALL = 'sum_all';
    public const INVOICE_SETTINGS_SUM_ALL_BY_PROJECT = 'sum_all_by_project';
    public const INVOICE_SETTINGS_SUM_ALL_BY_TASK = 'sum_all_by_task';
    public const INVOICE_SETTINGS_SUM_ALL_BY_JOB_TYPE = 'sum_records_by_job_type';
    public const INVOICE_SETTINGS_KEEP_AS_SEPARATE = 'keep_records_as_separate_invoice_items';

    //notify financial managers about new invoice
    public const INVOICE_NOTIFY_FINANCIAL_MANAGERS_NONE = 0; // 'Don't Notify Financial Managers';
    public const INVOICE_NOTIFY_FINANCIAL_MANAGERS_SELECTED = 1; // 'Notify Selected Financial Managers';
    public const INVOICE_NOTIFY_FINANCIAL_MANAGERS_ALL = 2; // 'Notify All Financial Managers';
    public const MAX_QR_NOTE_CONTENT_LENGTH = 500;

    public const INVOICE_TYPE = 'local';

    public function getHistoryFields(): array
    {
        return array_merge(
            parent::getHistoryFields(),
            [
                'number',
                'purchase_order_number',
                'project_id',
                'issued_on',
                'due_on',
                'is_canceled',
            ],
        );
    }

    public function getSearchFields(): array
    {
        return array_merge(
            parent::getSearchFields(),
            [
                'number',
                'purchase_order_number',
            ],
        );
    }

    /**
     * Return invoice project.
     *
     * @return Project|DataObject
     */
    public function getProject()
    {
        return DataObjectPool::get(Project::class, $this->getProjectId());
    }

    /**
     * Set invoice project.
     *
     * @param Project|null $project
     */
    public function setProject($project)
    {
        if ($project instanceof Project) {
            $this->setProjectId($project->getId());
        } elseif ($project === null) {
            $this->setProjectId(0);
        } else {
            throw new InvalidInstanceError('project', $project, 'We expected Project instance or NULL');
        }
    }

    /**
     * Return invoice status.
     */
    public function getStatus()
    {
        if ($this->getClosedOn()) {
            return $this->getIsCanceled() ? self::CANCELED : self::PAID;
        } else {
            return self::ISSUED;
        }
    }

    /**
     * Return true if this invoice is credit invoice and has total less then zero.
     *
     * @return bool
     */
    public function isCreditInvoice()
    {
        return $this->getTotal() <= 0;
    }

    /**
     * Returns true if this invoice is issued.
     *
     * @return bool
     */
    public function isIssued()
    {
        return $this->getStatus() === self::ISSUED;
    }

    /**
     * Returns true if this invoice is marked as paid.
     *
     * @return bool
     */
    public function isPaid()
    {
        return $this->getStatus() === self::PAID;
    }

    /**
     * Returns true if this invoice is canceled.
     *
     * @return bool
     */
    public function isCanceled()
    {
        return $this->getStatus() === self::CANCELED;
    }

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'based_on_type' => $this->getBasedOnType(),
            'based_on_id' => $this->getBasedOnId(),
            'number' => $this->getNumber(),
            'project_id' => (int) $this->getProjectId(),
            'purchase_order_number' => $this->getPurchaseOrderNumber(),
            'created_on' => $this->getCreatedOn(),
            'issued_on' => $this->getIssuedOn(),
            'due_on' => $this->getDueOn(),
            'closed_on' => $this->getClosedOn(), // Fully paid or canceled
            'sent_on' => $this->getSentOn(),
            'hash' => $this->getHash(),
            'status' => $this->getStatus(),
            'public_url' => $this->getPublicUrl(),
            'is_credit_invoice' => $this->isCreditInvoice(),
            'is_muted' => $this->getIsMuted(),
            'related_projects' => $this->getRelatedProjectIdsAndNames(),
            'last_payment_on' => $this->getLastPaymentOn(),
            'qr_note' => $this->getQrNote(),
            'qr_note_content' => $this->getQrNoteContent(),
        ]);
    }

    /**
     * Return invoice related projects.
     *
     * @return Project[]|DataObject[]
     */
    public function getRelatedProjects()
    {
        return DataObjectPool::getByIds(Project::class, $this->getRelatedProjectIds());
    }

    /**
     * Return a list of related project IDs.
     *
     * @return int[]
     */
    public function getRelatedProjectIds()
    {
        return TrackingObjects::getProjectIdsFromTrackingObjectIds($this->getTimeRecordIds(), $this->getExpenseIds());
    }

    /**
     * Return a list of relate project IDs and names.
     *
     * @return array
     */
    public function getRelatedProjectIdsAndNames()
    {
        $related_projects = $this->getRelatedProjects();
        $projects = [];

        if (is_foreachable($related_projects)) {
            foreach ($related_projects as $related_project) {
                $projects[] = [
                    'id' => $related_project->getId(),
                    'name' => $related_project->getName(),
                ];
            }
        }

        return $projects;
    }

    /**
     * Describe single.
     */
    public function describeSingleForFeather(array &$result)
    {
        parent::describeSingleForFeather($result);

        $result['time_records'] = $this->getTimeRecords();
        $result['expenses'] = $this->getExpenses();
        $result['payments'] = $this->getPayments();

        foreach (['time_records', 'expenses', 'payments'] as $k) {
            if (empty($result[$k])) {
                $result[$k] = [];
            }
        }
    }

    /**
     * @return DataObject|IInvoiceBasedOn|null
     */
    public function getBasedOn()
    {
        return $this->getBasedOnType() ? DataObjectPool::get($this->getBasedOnType(), $this->getBasedOnId()) : null;
    }

    /**
     * Set invoice based on.
     *
     * @param DataObject|IInvoiceBasedOn|null $based_on
     */
    public function setBasedOn($based_on)
    {
        if ($based_on === null) {
            $this->setBasedOnType(null);
            $this->setBasedOnId(null);
        } elseif ($based_on instanceof IInvoiceBasedOn) {
            $this->setBasedOnType(get_class($based_on));
            $this->setBasedOnId($based_on->getId());
        } else {
            throw new InvalidInstanceError('based_on', $based_on, 'IInvoiceBasedOn');
        }
    }

    /**
     * Return information about user who closed this invoice (paid or canceled).
     *
     * @return User|AnonymousUser
     */
    public function getClosedBy()
    {
        return $this->getUserFromFieldSet('closed_by');
    }

    /**
     * Set info about user who closed this invoice (paid or canceled).
     *
     * @param  AnonymousUser|User|null $user
     * @return AnonymousUser|User|null
     */
    public function setClosedBy($user)
    {
        return $this->setUserFromFieldSet($user, 'closed_by');
    }

    public function getRoutingContext(): string
    {
        return 'invoice';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'invoice_id' => $this->getId(),
        ];
    }

    public function addItem(
        array $attributes,
        int $position,
        bool $bulk = false,
        VariableProcessorInterface $variable_processor = null
    )
    {
        try {
            DB::beginWork('Begin: add item to an invoice @ ' . __CLASS__);

            $item = parent::addItem($attributes, $position, $bulk, $variable_processor);

            if (isset($attributes['time_record_ids']) && is_foreachable($attributes['time_record_ids'])) {
                DB::execute('UPDATE time_records SET invoice_item_id = ?, invoice_type = ?, billable_status = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?)', $item->getId(), Invoice::INVOICE_TYPE, TimeRecord::PENDING_PAYMENT, $attributes['time_record_ids']);

                TimeRecords::clearCacheFor($attributes['time_record_ids']);
                AngieApplication::cache()->removeByObject($this, 'time_record_ids');
            }

            if (isset($attributes['expense_ids']) && is_foreachable($attributes['expense_ids'])) {
                DB::execute('UPDATE expenses SET invoice_item_id = ?, invoice_type = ?, billable_status = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?)', $item->getId(), Invoice::INVOICE_TYPE, Expense::PENDING_PAYMENT, $attributes['expense_ids']);

                Expenses::clearCacheFor($attributes['expense_ids']);
                AngieApplication::cache()->removeByObject($this, 'expense_ids');
            }

            DB::commit('Done: add item to an invoice @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: add item to an invoice @ ' . __CLASS__);

            throw $e;
        }
    }

    /**
     * Return amount left for paying.
     *
     * @return float
     */
    private function calculateAmountToPay()
    {
        $taxed_total = str_replace(',', '.', strval($this->getRoundedTotal()));
        $paid_amount = str_replace(',', '.', strval($this->getPaidAmount()));

        $decimal_spaces = $this->getCurrency() instanceof Currency ? $this->getCurrency()->getDecimalSpaces() : 3;

        if (function_exists('bcsub')) {
            $left_to_pay = bcsub($taxed_total, $paid_amount, $decimal_spaces);
        } else {
            $left_to_pay = $taxed_total - $paid_amount;
        }

        return $left_to_pay < 0 ? 0 : $left_to_pay;
    }

    /**
     * Return public page, where invoice can be paid or downloaded.
     *
     * @param  IUser|null $user
     * @return string
     */
    public function getPublicUrl($user = null)
    {
        $query_params = ['number' => $this->getNumber(), 'hash' => $this->getHash()];

        if ($user instanceof IUser) {
            $query_params['recipient'] = base64_encode($user->getEmail() . ',' . $user->getName());
        }

        return AngieApplication::getContainer()
            ->get(RouterInterface::class)
                ->assemble('invoice_public', $query_params);
    }

    /**
     * Mark this invoice as canceled.
     *
     * @return $this
     */
    public function &markAsCanceled(User $by)
    {
        if (!$this->isCanceled()) {
            try {
                DB::beginWork('Begin: mark as canceled @ ' . __CLASS__);

                $this->releaseRelatedRecords();
                $this->releasePayments();

                $this->setClosedOn(DateTimeValue::now());
                $this->setIsCanceled(true);
                $this->setClosedBy($by);

                $this->recalculate();
                $this->save();

                DB::commit('Done: mark as canceled @ ' . __CLASS__);
            } catch (Exception $e) {
                DB::rollback('Rollback: mark as canceled @ ' . __CLASS__);

                throw $e;
            }
        }

        return $this;
    }

    public function getSearchDocument(): SearchDocumentInterface
    {
        return new InvoiceSearchDocument($this);
    }

    // ---------------------------------------------------
    //  Payments
    // ---------------------------------------------------

    /**
     * Return true if payment can be made.
     *
     * @return bool
     */
    public function canMakePayment()
    {
        return $this->isIssued() && !$this->isCreditInvoice() && Payments::hasConfiguredGateway($this) && !$this->isCurrencyConflict();
    }

    /**
     * Return true if there is conflict with invoice currency and currency of paypal credit card.
     *
     * @return bool
     */
    public function isCurrencyConflict()
    {
        $credit_card = Payments::getCreditCardGateway($this);
        if ($credit_card instanceof PaypalDirectGateway && $credit_card->getIsEnabled()) {
            $paypal = Payments::getPayPalGateway($this);

            return ($paypal instanceof PaymentGateway && !$paypal->getIsEnabled())           // paypal express is disabled and
                && $credit_card->getProcessorCurrency() !== $this->getCurrency()->getCode(); // currencies are not equal
        }

        return false;
    }

    /**
     * Return true if we can store a card for this invoice (it makes sense to store cards only for invoices created by
     * recurring profiles).
     *
     * @return bool
     */
    public function canStoreCard()
    {
        return $this->getBasedOn() instanceof RecurringProfile;
    }

    public function markAsPaid(User $by, DateTimeValue $on, bool $save = false)
    {
        $this->setClosedBy($by);
        $this->setClosedOn($on);
        $this->setLastPaymentOn($on);

        if ($save) {
            $this->save();
        }
    }

    /**
     * Record new payment.
     */
    public function recordNewPayment(Payment $payment)
    {
        if ($this->isPaid() || $this->isCanceled()) {
            throw new InvalidParamError('payment', $payment, "Can't add payments to closed invoice");
        }

        if ($payment->getAmount() > $this->getBalanceDue() && $payment->isCustom()) {
            throw new InvalidParamError('payment', $payment, "Amount can't be larger than " . $this->getBalanceDue());
        } elseif ($payment->getAmount() < $this->getBalanceDue() && !$payment->isCustom()) {
            throw new InvalidParamError('payment', $payment, "This invoice can't be paid partially");
        }

        $this->recalculate();

        if ($this->getBalanceDue() == 0 && !$this->getClosedOn()) {
            $this->setClosedBy($payment->getCreatedBy());
            $this->setClosedOn($this->paymentCreationToInvoicePaidOnDate($payment->getCreatedOn()));

            $this->setBillableStatusForRelatedRecords(ITrackingObject::PAID);
        }

        $this->setLastPaymentOn($payment->getPaidOn());
        $this->save();

        $this->notifyOnInvoicePaid();
    }

    /**
     * Record whne payment made to this object is updated.
     */
    public function recordPaymentUpdate(Payment $payment)
    {
        $this->recalculate();

        if ($this->getClosedOn()) {
            if ($this->calculateAmountToPay() > 0) {
                $this->setClosedBy(null);
                $this->setClosedOn(null);

                $this->setBillableStatusForRelatedRecords(ITrackingObject::PENDING_PAYMENT);
            }
        } else {
            if ($this->calculateAmountToPay() == 0) {
                $this->setClosedBy($payment->getCreatedBy());
                $this->setClosedOn($this->paymentCreationToInvoicePaidOnDate($payment->getCreatedOn()));

                $this->setBillableStatusForRelatedRecords(ITrackingObject::PAID);

                $notify = true;
            }
        }

        $this->setLastPaymentOn($payment->getPaidOn());
        $this->save();

        if (isset($notify) && $notify) {
            $this->notifyOnInvoicePaid();
        }
    }

    /**
     * Prepare invoice paid on DateValue from payment created_on timestamp (date and time).
     *
     * @return DateValue
     */
    private function paymentCreationToInvoicePaidOnDate(DateTimeValue $payment_created_on)
    {
        return DateValue::makeFromTimestamp($payment_created_on->advance(Globalization::getGmtOffset(), false)->getTimestamp());
    }

    /**
     * Record when payment made to this object is removed.
     */
    public function recordPaymentRemoval()
    {
        $this->recalculate();

        if ($this->getClosedOn()) {
            $this->setClosedBy(null);
            $this->setClosedOn(null);

            $this->setBillableStatusForRelatedRecords(ITrackingObject::PENDING_PAYMENT);
        }

        $this->save();
    }

    /**
     * Send email notification to people to notify them that this invoice is paid.
     */
    private function notifyOnInvoicePaid()
    {
        $recipients = $this->getRecipientInstances();

        if ($recipients && is_foreachable($recipients)) {
            if ($this->getClosedOn()) {
                AngieApplication::notifications()
                    ->notifyAbout('invoicing/invoice_paid', $this)
                    ->sendToUsers($recipients);
            } else {
                AngieApplication::notifications()
                    ->notifyAbout('invoicing/invoice_partially_paid', $this)
                    ->sendToUsers($recipients);
            }
        }
    }

    // ---------------------------------------------------
    //  Send
    // ---------------------------------------------------

    /**
     * Sent an invoice to the client.
     *
     * @param  User|IUser $sender
     * @param  IUser[]    $recipients
     * @param  string     $subject
     * @param  string     $message
     * @return $this
     */
    public function &send($sender, $recipients, $subject, $message)
    {
        if ($this->isCreditInvoice() && !$this->isPaid()) {
            $this->markAsPaid($sender, new DateTimeValue());
        }

        if ($recipients && is_foreachable($recipients)) {
            $recipient_addresses = [];

            foreach ($recipients as $recipient) {
                if ($recipient->getDisplayName()) {
                    $recipient_addresses[] = $recipient->getDisplayName() . ' <' . $recipient->getEmail() . '>';
                } else {
                    $recipient_addresses[] = $recipient->getEmail();
                }
            }

            $this->setRecipients(implode(', ', $recipient_addresses));
        }

        $this->setEmailFrom($sender);
        $this->setEmailSubject($subject);
        $this->setEmailBody($message);
        $this->setSentOn(DateTimeValue::now());

        $this->save();

        // Forget object in the pool now that it is updated, so we get a fresh instance in the email templates.
        DataObjectPool::forget(
            Invoice::class,
            $this->getId(),
        );

        AngieApplication::notifications()->notifyAbout('invoicing/send_invoice', $this, $sender)
            ->setCustomSubject($subject)
            ->setCustomMessage($message)
            ->sendToUsers($recipients, true);

        return $this;
    }

    // ---------------------------------------------------
    //  Duplicate
    // ---------------------------------------------------

    /**
     * Duplicate this invoice with and set a given number.
     *
     * @param  string $number
     * @return $this
     */
    public function &duplicate($number)
    {
        /** @var Invoice $copy */
        $copy = $this->copy(false);

        $copy->setBasedOn(null);
        $copy->setIssuedOn(DateValue::now());
        $copy->setDueOn(DateValue::now());
        $copy->setNumber($number);
        $copy->setClosedBy(null);
        $copy->setClosedOn(null);
        $copy->setHash(null);

        $copy->save();

        foreach ($this->getItems() as $item) {
            $item_copy = $item->copy(false);

            if ($item_copy instanceof InvoiceItem) {
                $item_copy->setParent($copy);
                $item_copy->save();
            }
        }

        $copy->recalculate();
        $copy->save();

        return $copy;
    }

    /**
     * Return invoice name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->getNumber();
    }

    // ---------------------------------------------------
    //  Status
    // ---------------------------------------------------

    /**
     * Check if this invoice is overdue.
     *
     * @return bool
     */
    public function isOverdue()
    {
        $today = new DateValue(AngieApplication::currentTimestamp()->getCurrentTimestamp() + Globalization::getUserGmtOffset());
        $due_on = $this->getDueOn();

        return (bool) ($this->isIssued() && !$this->isPaid() && !$this->isCanceled() && ($due_on instanceof DateValue && ($due_on->toMySQL() < $today->toMySQL())));
    }

    // ---------------------------------------------------
    //  Related records
    // ---------------------------------------------------

    /**
     * Return array of related time record ID-s.
     *
     * @param  bool  $use_cache
     * @return array
     */
    public function getTimeRecordIds($use_cache = true)
    {
        return AngieApplication::cache()->getByObject(
            $this,
            'time_record_ids',
            function () {
                return DB::executeFirstColumn('SELECT id FROM time_records WHERE invoice_type = ? AND invoice_item_id IN (SELECT id FROM invoice_items WHERE parent_type = "Invoice" AND parent_id = ?) ORDER BY id', Invoice::INVOICE_TYPE, $this->getId());
            },
            empty($use_cache),
        );
    }

    /**
     * Return related time records.
     *
     * @return DBResult|TimeRecord[]
     */
    public function getTimeRecords()
    {
        return TimeRecords::findBySQL(
            'SELECT * FROM time_records WHERE invoice_type = ? AND invoice_item_id IN (SELECT id FROM invoice_items WHERE parent_type = "Invoice" AND parent_id = ?) ORDER BY id',
            Invoice::INVOICE_TYPE,
            $this->getId(),
        );
    }

    /**
     * Return array of related expense ID-s.
     *
     * @param  bool  $use_cache
     * @return array
     */
    public function getExpenseIds($use_cache = false)
    {
        return AngieApplication::cache()->getByObject($this, 'expense_ids', function () {
            return DB::executeFirstColumn('SELECT id FROM expenses WHERE invoice_type = ? AND invoice_item_id IN (SELECT id FROM invoice_items WHERE parent_type = "Invoice" AND parent_id = ?)', Invoice::INVOICE_TYPE, $this->getId());
        }, empty($use_cache));
    }

    /**
     * Return related expenses.
     *
     * @return DBResult|Expense[]
     */
    public function getExpenses()
    {
        return Expenses::findBySQL(
            'SELECT * FROM expenses WHERE invoice_type = ? AND invoice_item_id IN (SELECT id FROM invoice_items WHERE parent_type = "Invoice" AND parent_id = ?) ORDER BY id',
            Invoice::INVOICE_TYPE,
            $this->getId(),
        );
    }

    /**
     * Release related time and expense records.
     */
    public function releaseRelatedRecords()
    {
        $time_record_ids = $this->getTimeRecordIds();

        if ($time_record_ids) {
            DB::execute('UPDATE time_records SET invoice_item_id = ?, invoice_type = ?, billable_status = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?)', 0, null, TimeRecord::BILLABLE, $time_record_ids);
            AngieApplication::getContainer()->get(TrackingServiceInterface::class)->calcRatesForTimeRecordsIds($time_record_ids);
            TimeRecords::clearCacheFor($time_record_ids);
        }

        if ($expense_ids = $this->getExpenseIds()) {
            DB::execute('UPDATE expenses SET invoice_item_id = ?, invoice_type = ?, billable_status = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?)', 0, null, Expense::BILLABLE, $expense_ids);
            Expenses::clearCacheFor($expense_ids);
        }

        AngieApplication::cache()->removeByObject($this);
    }

    /**
     * Set billable status for related records.
     *
     * @param int $status
     */
    private function setBillableStatusForRelatedRecords($status)
    {
        if ($invoice_item_ids = DB::executeFirstColumn('SELECT id FROM invoice_items WHERE parent_type = "Invoice" AND parent_id = ?', $this->getId())) {
            $escaped = DB::escape($invoice_item_ids);

            if ($time_record_ids = $this->getTimeRecordIds()) {
                DB::execute("UPDATE time_records SET billable_status = ?, updated_on = UTC_TIMESTAMP() WHERE invoice_type = ? AND invoice_item_id IN ($escaped)", $status, Invoice::INVOICE_TYPE);
                TimeRecords::clearCacheFor($time_record_ids);
            }

            if ($expense_ids = $this->getExpenseIds()) {
                DB::execute("UPDATE expenses SET billable_status = ?, updated_on = UTC_TIMESTAMP() WHERE invoice_type = ? AND invoice_item_id IN ($escaped)", $status, Invoice::INVOICE_TYPE);
                Expenses::clearCacheFor($expense_ids);
            }
        }

        AngieApplication::cache()->removeByObject($this, 'time_record_ids');
        AngieApplication::cache()->removeByObject($this, 'expense_ids');
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------

    public function validate(ValidationErrors &$errors)
    {
        if (!$this->validatePresenceOf('company_name')) {
            $errors->fieldValueIsRequired('company_name');
        }

        if (!$this->validatePresenceOf('company_address')) {
            $errors->fieldValueIsRequired('company_address');
        }

        if ($this->validatePresenceOf('number')) {
            if (!$this->validateUniquenessOf('number')) {
                $errors->addError('Invoice number needs to be unique', 'number');
            }
        } else {
            $errors->fieldValueIsRequired('number');
        }

        parent::validate($errors);
    }

    public function save()
    {
        if (!$this->getIssuedOn()) {
            $this->setIssuedOn(DateValue::now());
        }

        if (!empty($this->getQrNoteContent()) && strlen($this->getQrNoteContent()) > self::MAX_QR_NOTE_CONTENT_LENGTH) {
            throw new InvalidArgumentException(lang("QR Note content can't be longer than 500 characters"));
        }

        if (!$this->getDueOn()) {
            $this->setDueOn($this->getIssuedOn());
        }

        $this->recalculate();

        parent::save();
    }

    public function delete($bulk = false)
    {
        try {
            DB::beginWork('Begin: delete invoice @ ' . __CLASS__);

            $this->releaseRelatedRecords();

            parent::delete($bulk);

            DB::commit('Done: delete invoice @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: delete invoice @ ' . __CLASS__);

            throw $e;
        }
    }
}
