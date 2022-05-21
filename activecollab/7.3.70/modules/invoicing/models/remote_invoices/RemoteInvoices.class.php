<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class RemoteInvoices extends BaseRemoteInvoices
{
    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): RemoteInvoice
    {
        $based_on = array_key_exists('based_on', $attributes) && !empty($attributes['based_on']) ? $attributes['based_on'] : RemoteInvoice::BASED_ON_TIME_AND_EXPENSES;
        $items = isset($attributes['items']) && is_array($attributes['items']) ? $attributes['items'] : [];

        try {
            DB::beginWork('Begin: update time records and expenses id to pending payment status @ ' . __CLASS__);

            $invoice = parent::create($attributes, false, false);
            $invoice->save();

            foreach ($items as $item) {
                $invoice->addItem($item, $based_on);
            }

            DB::commit('Done: update time records and expenses id to pending payment status @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: update time records and expenses id to pending payment status @ ' . __CLASS__);
            throw $e;
        }

        return $invoice;
    }

    /**
     * Return record ids from items by record type.
     *
     * @param  string $type
     * @return array
     */
    public static function getRecordIdsFromItemsByRecordType(array $items, $type)
    {
        $result = [];
        $key = $type . '_ids';

        foreach ($items as $item) {
            if (isset($item[$key]) && is_array($item[$key])) {
                $result = array_merge($result, $item[$key]);
            }
        }

        return $result;
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): RemoteInvoice
    {
        $items_line_ids = $instance->getItemsLineIds();
        /** @var RemoteInvoice $instance $items */
        $items = isset($attributes['items']) && is_array($attributes['items']) ? $attributes['items'] : array_flip($items_line_ids);

        try {
            DB::beginWork('Begin: update time records and expenses id to pending payment status @ ' . __CLASS__);
            parent::update($instance, $attributes, false);
            $tracking_objects_status = $instance->isPaid() ? TimeRecord::PAID : TimeRecord::PENDING_PAYMENT;

            $items_for_deletion = array_filter($items_line_ids, function ($line_id) use ($items) {
                $line_items = array_keys($items);

                return !in_array($line_id, $line_items);
            });
            if (count($items_for_deletion)) {
                $instance->deleteLineItems($items_for_deletion);
            }
            $instance->updateTrackingObjectsStatus($tracking_objects_status);
            $instance->save();

            if ($instance->getBasedOn() === 'fixed' && isset($attributes['items']) && is_array($attributes['items'])) {
                $new_item_ids = array_keys($attributes['items']);
                $prev_items = RemoteInvoiceItems::findBy(['parent_id' => $instance->getId()]);
                if ($prev_items) {
                    /** @var RemoteInvoiceItem[] $items */
                    $items = $prev_items->toArray();
                    foreach ($items as $prev_item) {
                        $item_id = $prev_item->getLineIdString();
                        if (in_array($item_id, $new_item_ids)) {
                            $prev_item->setAmount((float) $attributes['items'][$item_id]);
                            $prev_item->save();
                        }
                    }
                }
            }

            DB::commit('Done: update time records and expenses id to pending payment status @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: update time records and expenses id to pending payment status @ ' . __CLASS__);
            throw $e;
        }

        return $instance;
    }

    /**
     * @param DataObject|RemoteInvoice $instance
     */
    public static function scrap(
        DataObject &$instance,
        bool $force_delete = false
    )
    {
        $time_record_ids = DB::executeFirstColumn('SELECT id FROM time_records WHERE invoice_type = ? AND invoice_item_id IN (SELECT id FROM remote_invoice_items WHERE parent_type = ? AND parent_id = ?)', RemoteInvoice::INVOICE_TYPE, get_class($instance), $instance->getId());
        $expense_ids = DB::executeFirstColumn('SELECT id FROM expenses WHERE invoice_type = ? AND invoice_item_id IN (SELECT id FROM remote_invoice_items WHERE parent_type = ? AND parent_id = ?)', RemoteInvoice::INVOICE_TYPE, get_class($instance), $instance->getId());

        try {
            DB::beginWork('Begin: update time records and expenses id to billable payment status @ ' . __CLASS__);

            if (!empty($time_record_ids)) {
                DB::execute('UPDATE time_records SET invoice_item_id = ?, invoice_type = ?, billable_status = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?)', 0, null, TimeRecord::BILLABLE, $time_record_ids);
                TimeRecords::clearCacheFor($time_record_ids);
            }

            if (!empty($expense_ids)) {
                DB::execute('UPDATE expenses SET invoice_item_id = ?, invoice_type = ?, billable_status = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?)', 0, null, TimeRecord::BILLABLE, $expense_ids);
                Expenses::clearCacheFor($expense_ids);
            }

            $invoice_items = RemoteInvoiceItems::findBy(['parent_id' => $instance->getId()]);
            if ($invoice_items) {
                /** @var RemoteInvoiceItem[] $items */
                $items = $invoice_items->toArray();
                foreach ($items as $item) {
                    $item->delete();
                }
            }

            parent::scrap($instance, $force_delete);

            DB::commit('Done: update time records and expenses id to billable payment status @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Rollback: update time records and expenses id to billable payment status @ ' . __CLASS__);
            throw $e;
        }
    }

    /**
     * Return new collection.
     *
     * @param  User|null         $user
     * @return ModelCollection
     * @throws InvalidParamError
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);

        if ($collection_name == 'active_remote_invoices') {
            $collection->setOrderBy('updated_on DESC');
        } elseif (str_starts_with($collection_name, 'archived_remote_invoices')) {
            $collection->setConditions('amount = balance');
            $collection->setOrderBy('updated_on DESC');

            $bits = explode('_', $collection_name);
            $collection->setPagination(array_pop($bits), 30);
        } else {
            throw new InvalidParamError('collection_name', $collection_name);
        }

        return $collection;
    }

    /**
     * Update local invoices balance with new one.
     */
    public static function updateBalance(array $balances)
    {
        foreach ($balances as $balance) {
            DB::execute('UPDATE remote_invoices SET balance = ?, updated_on = UTC_TIMESTAMP() WHERE remote_code = ?', $balance['balance'], $balance['remote_code']);
        }
        AngieApplication::cache()->removeByModel(static::getModelName(true));
    }

    abstract public static function countInvoicesStatus();

    protected static function countByStatus(string $type)
    {
        /** @var RemoteInvoice[] $remote_invoices */
        $remote_invoices = self::find(['conditions' => ['type = ?', $type]]);

        $statuses = self::prepareInitialValues();

        if (is_foreachable($remote_invoices)) {
            foreach ($remote_invoices as $remote_invoice) {
                ++$statuses[$remote_invoice->getStatus()];
            }
        }

        return [
            $remote_invoices ? count($remote_invoices) : 0,
            $statuses[RemoteInvoice::PAID],
            $statuses[RemoteInvoice::PAID_AND_CANCELED],
            $statuses[RemoteInvoice::UNSENT],
            $statuses[RemoteInvoice::SENT],
            $statuses[RemoteInvoice::PARTIALLY_PAID],
        ];
    }

    private static function prepareInitialValues(): array
    {
        $statuses = [];

        foreach (RemoteInvoice::STATUSES as $status) {
            $statuses[$status] = 0;
        }

        return $statuses;
    }
}
