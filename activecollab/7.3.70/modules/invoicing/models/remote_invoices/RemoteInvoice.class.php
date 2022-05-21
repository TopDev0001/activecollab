<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class RemoteInvoice extends BaseRemoteInvoice
{
    const UNSENT = 'unsent';
    const PAID = 'paid';
    const SENT = 'sent';
    const PAID_AND_CANCELED = 'paid_and_canceled';
    const PARTIALLY_PAID = 'partially_paid';

    const BASED_ON_TIME_AND_EXPENSES = 'time_and_expenses';
    const BASED_ON_FIXED = 'fixed';

    const STATUSES = [
        self::UNSENT,
        self::PAID,
        self::SENT,
        self::PAID_AND_CANCELED,
        self::PARTIALLY_PAID,
    ];

    const INVOICE_TYPE = 'remote';

    protected function __configure(): void
    {
        parent::__configure();

        $this->setType(get_class($this));
    }

    abstract public function getStatus();

    /**
     * Return array or property => value pairs that describes this object.
     */
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        return array_merge($result, [
            'client' => $this->getClient(),
            'remote_code' => $this->getRemoteCode(),
            'amount' => $this->getAmount(),
            'number' => $this->getInvoiceNumber(),
            'balance' => $this->getBalance(),
            'items' => $this->getItems(),
            'is_paid' => $this->isPaid(),
        ]);
    }

    public function getItems(): array
    {
        $items = [];
        $line_item_ids = DB::executeFirstColumn('SELECT id FROM remote_invoice_items WHERE parent_type = ? AND parent_id = ?', get_class($this), $this->getId());
        if ($line_item_ids) {
            foreach ($line_item_ids as $line_item_id) {
                $time_record_ids = DB::executeFirstColumn('SELECT id FROM time_records WHERE invoice_type = ? AND invoice_item_id = ?', RemoteInvoice::INVOICE_TYPE, $line_item_id);
                $expense_ids = DB::executeFirstColumn('SELECT id FROM expenses WHERE invoice_type = ? AND invoice_item_id = ?', RemoteInvoice::INVOICE_TYPE, $line_item_id);
                $items[] = [
                    'time_record_ids' => $time_record_ids ?? [],
                    'expense_ids' => $expense_ids ?? [],
                    'line_id' => DB::executeFirstCell('SELECT line_id_string FROM remote_invoice_items WHERE id = ?', $line_item_id),
                ];
            }
        }

        return $items;
    }

    public function getItemsLineIds(): array
    {
        $result = DB::executeIdNameMap('SELECT id, line_id_string as name FROM remote_invoice_items WHERE parent_type = ? AND parent_id = ?', get_class($this), $this->getId());

        return $result ?? [];
    }

    public function deleteLineItems(array $line_ids): void
    {
        try {
            DB::beginWork('Begin: delete line items @ ' . __CLASS__);
            $ids = DB::executeFirstColumn('SELECT id FROM remote_invoice_items WHERE parent_type = ? AND parent_id = ? AND line_id_string IN (?)', get_class($this), $this->getId(), $line_ids);
            if ($ids) {
                DB::execute('DELETE FROM remote_invoice_items WHERE id IN (?)', $ids);
                RemoteInvoiceItems::clearCacheFor($ids);

                // release the time records
                $time_record_ids = DB::executeFirstColumn('SELECT id FROM time_records WHERE invoice_type = ? AND invoice_item_id IN (?)', RemoteInvoice::INVOICE_TYPE, $ids);
                if ($time_record_ids) {
                    DB::execute('UPDATE time_records SET billable_status = ?, updated_on = UTC_TIMESTAMP(), invoice_type = ?, invoice_item_id = ? WHERE id IN (?)', TimeRecord::BILLABLE, null, 0, $time_record_ids);
                    TimeRecords::clearCacheFor($time_record_ids);
                }

                // release the expenses
                $expense_ids = DB::executeFirstColumn('SELECT id FROM expenses WHERE invoice_type = ? AND invoice_item_id IN (?)', RemoteInvoice::INVOICE_TYPE, $ids);
                if ($expense_ids) {
                    DB::execute('UPDATE expenses SET billable_status = ?, updated_on = UTC_TIMESTAMP(), invoice_type = ?, invoice_item_id = ? WHERE id IN (?)', Expense::BILLABLE, null, 0, $expense_ids);
                    Expenses::clearCacheFor($expense_ids);
                }
            }
            DB::commit('Done: delete line items @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: delete line items @ ' . __CLASS__);
            throw $e;
        }
    }

    public function updateTrackingObjectsStatus(int $status): void
    {
        try {
            DB::beginWork('Begin: update tracking objects status @ ' . __CLASS__);
            $ids = DB::executeFirstColumn('SELECT id FROM remote_invoice_items WHERE parent_type = ? AND parent_id = ?', get_class($this), $this->getId());
            if ($ids) {
                $time_record_ids = DB::executeFirstColumn('SELECT id FROM time_records WHERE invoice_type = ? AND invoice_item_id IN (?)', RemoteInvoice::INVOICE_TYPE, $ids);
                if ($time_record_ids) {
                    DB::execute('UPDATE time_records SET billable_status = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?)', $status, $time_record_ids);
                    TimeRecords::clearCacheFor($time_record_ids);
                }

                $expense_ids = DB::executeFirstColumn('SELECT id FROM expenses WHERE invoice_type = ? AND invoice_item_id IN (?)', RemoteInvoice::INVOICE_TYPE, $ids);
                if ($expense_ids) {
                    DB::execute('UPDATE expenses SET billable_status = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?)', $status, $expense_ids);
                    Expenses::clearCacheFor($expense_ids);
                }
            }
            DB::commit('Done: update tracking objects status @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: update tracking objects status @ ' . __CLASS__);
            throw $e;
        }
    }

    /**
     * Return true if invoice is paid.
     *
     * @return bool
     */
    public function isPaid()
    {
        return !($this->getBalance() > 0);
    }

    /**
     * Return true if invoice is canceled.
     *
     * @return bool
     */
    public function isCanceled()
    {
        return !($this->getAmount() > 0);
    }

    public function addItem(array $attributes, string $based_on): void
    {
        // add an remote invoice item record
        $invoice_item = RemoteInvoiceItems::create([
            'parent_type' => get_class($this),
            'parent_id' => $this->getId(),
            'line_id_string' => array_key_exists('line_id', $attributes) ? $attributes['line_id'] : '',
            'amount' => $based_on === RemoteInvoice::BASED_ON_FIXED && isset($attributes['unit_cost']) ? $attributes['unit_cost'] : 0,
            'project_id' => $based_on === RemoteInvoice::BASED_ON_FIXED && array_key_exists('project_id', $attributes) ? $attributes['project_id'] : 0,
        ]);

        // if an invoice is based on time and expenses do the tracking objects work
        if ($based_on === RemoteInvoice::BASED_ON_TIME_AND_EXPENSES) {
            $time_record_ids = array_key_exists('time_record_ids', $attributes) ? $attributes['time_record_ids'] : [];
            $expense_ids = array_key_exists('expense_ids', $attributes) ? $attributes['expense_ids'] : [];

            if (count($time_record_ids)) {
                DB::execute('UPDATE time_records SET invoice_item_id = ?, invoice_type = ?, billable_status = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?)', $invoice_item->getId(), RemoteInvoice::INVOICE_TYPE, TimeRecord::PENDING_PAYMENT, $time_record_ids);
                TimeRecords::clearCacheFor($time_record_ids);
            }

            if (count($expense_ids)) {
                DB::execute('UPDATE expenses SET invoice_item_id = ?, invoice_type = ?, billable_status = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?)', $invoice_item->getId(), RemoteInvoice::INVOICE_TYPE, Expense::PENDING_PAYMENT, $expense_ids);
                Expenses::clearCacheFor($expense_ids);
            }
        }
    }
}
