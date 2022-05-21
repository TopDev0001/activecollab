<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class XeroInvoices extends RemoteInvoices
{
    const SYNC_TIME_STAMP_KEY = 'XERO_SYNC_TIME_STAMP';

    public static function canAdd(User $user): bool
    {
        return $user->isFinancialManager();
    }

    /**
     * Return new collection.
     *
     * @param  User|null       $user
     * @return ModelCollection
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);
        $collection->setConditions("type = 'XeroInvoice'");

        return $collection;
    }

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): RemoteInvoice
    {
        $xero_invoice = null;

        DB::transact(function () use (&$xero_invoice, $attributes, $save, $announce) {
            $item_ids = [];
            if (isset($attributes['items']) && is_array($attributes['items'])) {
                foreach (array_keys($attributes['items']) as $key) {
                    $item_ids[] = $key;
                }
            } else {
                $attributes['items'] = [];
            }

            /** @var XeroPHP\Models\Accounting\Invoice $invoice */
            $invoice = self::getXeroIntegration()->createInvoice($attributes);

            $attributes = array_merge($attributes, [
                'remote_code' => $invoice->getInvoiceID(),
                'amount' => $invoice->getTotal(),
                'client' => $invoice->getContact()->getName(),
                'invoice_number' => $invoice->getInvoiceNumber(),
                'balance' => $invoice->getAmountDue(),
            ]);

            $attributes['type'] = 'XeroInvoice';

            foreach ($invoice->getLineItems() as $line) {
                foreach ($item_ids as $key => $item_id) {
                    if ($attributes['items'][$item_id]['unit_cost'] == $line->getUnitAmount()
                        && $attributes['items'][$item_id]['quantity'] == $line->getQuantity()
                        && $attributes['items'][$item_id]['description'] == $line->getDescription()
                    ) {
                        $attributes['items'][$item_id]['line_id'] = $line->getLineItemID();
                        unset($item_ids[$key]);
                    }
                }
            }

            $xero_invoice = parent::create($attributes, $save, $announce);
        });

        return $xero_invoice;
    }

    /**
     * Return data service.
     *
     * @return Integration|XeroIntegration
     */
    public static function getXeroIntegration()
    {
        return Integrations::findFirstByType(XeroIntegration::class);
    }

    /**
     * Sync local Xero invoices with remote invoices.
     *
     * @return array
     */
    public static function sync(array $ids = [])
    {
        $conditions = [DB::prepare('type = ?', XeroInvoice::class)];

        if (!empty($ids)) {
            $conditions[] = DB::prepare('id IN (?)', $ids);
        }

        $conditions = implode(' AND ', $conditions);

        $remote_id_xero_invoice_id_map = [];
        $result = [];

        if ($xero_invoices = self::find(['conditions' => $conditions])) {
            foreach ($xero_invoices as $xero_invoice) {
                if ($xero_invoice instanceof XeroInvoice) {
                    $remote_id_xero_invoice_id_map[$xero_invoice->getRemoteCode()] = $xero_invoice;
                    $result[] = $xero_invoice;
                }
            }
        }

        // Collection of Entities in QueryResponse
        $remote_invoice_ids = array_keys($remote_id_xero_invoice_id_map);
        $sync_timestamp = DateTimeValue::now()->getTimestamp();
        $remote_invoices = self::getXeroIntegration()->fetch(XeroPHP\Models\Accounting\Invoice::class, $remote_invoice_ids, '', AngieApplication::memories()->get(self::SYNC_TIME_STAMP_KEY, $sync_timestamp));
        try {
            DB::beginWork('Updating xero invoices @ ' . __CLASS__);

            /** @var \XeroPHP\Models\Accounting\Invoice $remote_invoice */
            foreach ($remote_invoices as $remote_invoice) {
                $xero_invoice = isset($remote_id_xero_invoice_id_map[$remote_invoice->getInvoiceID()]) ? $remote_id_xero_invoice_id_map[$remote_invoice->getInvoiceID()] : null;

                if ($xero_invoice instanceof XeroInvoice) {
                    $xero_invoice_key = array_search($xero_invoice, $result, true);

                    if ($remote_invoice->getStatus() == \XeroPHP\Models\Accounting\Invoice::INVOICE_STATUS_DELETED) {
                        self::scrap($xero_invoice);
                        if ($xero_invoice_key !== false) {
                            unset($result[$xero_invoice_key]);
                        }
                        continue;
                    }

                    if ($xero_invoice->getXeroUpdateOn() != $remote_invoice->getUpdatedDateUTC()->getTimestamp() || ($xero_invoice->getEmailStatus() != XeroInvoice::EMAIL_STATUS_SENT && $remote_invoice->getSentToContact())) {
                        $attributes = [
                            'amount' => $remote_invoice->getTotal(),
                            'client' => $remote_invoice->getContact()->getName(),
                            'invoice_number' => $remote_invoice->getInvoiceNumber(),
                            'balance' => $remote_invoice->getAmountDue(),
                            'currency' => $remote_invoice->getCurrencyCode(),
                            'xero_update_on' => $remote_invoice->getUpdatedDateUTC()->getTimestamp(),
                            'xero_status' => $remote_invoice->getStatus(),
                        ];

                        if ($remote_invoice->getSentToContact()) {
                            $attributes['email_status'] = XeroInvoice::EMAIL_STATUS_SENT;
                        }

                        if ($remote_invoice->getTotal() != $xero_invoice->getAmount() || $remote_invoice->getAmountDue() != $xero_invoice->getBalance()) {
                            $remote_invoice = self::getXeroIntegration()->loadById('Accounting\\Invoice', $remote_invoice->getInvoiceID());
                            if (count($remote_invoice->getLineItems())) {
                                $attributes['items'] = array_reduce($remote_invoice->getLineItems()->getArrayCopy(), function ($items, $item) {
                                    $items[$item->getLineItemID()] = $item->getLineAmount();

                                    return $items;
                                }, []);
                            } else {
                                $attributes['items'] = [];
                            }
                        }

                        if ($remote_invoice->getStatus() == \XeroPHP\Models\Accounting\Invoice::INVOICE_STATUS_VOIDED) {
                            $attributes['items'] = [];
                        }

                        self::update($xero_invoice, $attributes);
                        $result[$xero_invoice_key] = $xero_invoice;
                    }
                }
            }

            AngieApplication::memories()->set(self::SYNC_TIME_STAMP_KEY, $sync_timestamp);

            DB::commit('Xero invoices updated @ ' . __CLASS__);

            return $result;
        } catch (Exception $e) {
            DB::rollback('Failed to update xero invoices @ ' . __CLASS__);
            throw $e;
        }
    }

    public static function &update(
        DataObject &$instance,
        array $attributes,
        bool $save = true
    ): RemoteInvoice
    {
        if (isset($attributes['email_status'])) {
            $instance->setEmailStatus($attributes['email_status']);
        }

        if (isset($attributes['xero_status'])) {
            $instance->setXeroStatus($attributes['xero_status']);
        }

        if (isset($attributes['currency'])) {
            $instance->setCurrency($attributes['currency']);
        }

        if (isset($attributes['xero_update_on'])) {
            $instance->setXeroUpdateOn($attributes['xero_update_on']);
        }

        parent::update($instance, $attributes, $save);

        return $instance;
    }

    public static function countInvoicesStatus()
    {
        return parent::countByStatus(XeroInvoice::class);
    }
}
