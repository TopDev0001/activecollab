<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Invoice implementation that can be attached to any object.
 *
 * @package ActiveCollab.modules.invoiving
 * @subpackage models
 */
trait IInvoiceBasedOnImplementation
{
    /**
     * Add items to the invoice.
     *
     * @param  array     $items
     * @return Invoice
     * @throws Exception
     */
    public function &commitInvoiceItems($items, Invoice &$invoice)
    {
        try {
            DB::beginWork('Saving invoice @ ' . __CLASS__);

            if ($invoice->isNew()) {
                $invoice->save();
            }

            $position = 1;

            foreach ($items as $invoice_item_data) {
                $invoice->addItem(
                    $invoice_item_data,
                    $position++,
                    true
                );
            }

            $invoice->recalculate();
            $invoice->save();

            DB::commit('Invoice saved @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to save invoice @ ' . __CLASS__);
            throw $e;
        }

        return $invoice;
    }

    /**
     * Create invoice from given properties.
     *
     * @param  string  $number
     * @param  string  $client_address
     * @param  array   $additional
     * @return Invoice
     */
    protected function createInvoiceFromPropeties($number, Company $client, $client_address = null, $additional = null)
    {
        $invoice = new Invoice();
        $invoice->setNumber($number);

        $project_id = array_var($additional, 'project_id');

        if ($project_id) {
            $invoice->setProjectId($project_id);
        }

        $invoice->setCompanyId($client->getId());
        $invoice->setCompanyName($client->getName());
        $invoice->setCompanyAddress($client_address);
        $invoice->setBasedOn($this);
        $invoice->setDueOn(new DateValue());

        if (isset($additional['private_note']) && $additional['private_note']) {
            $invoice->setPrivateNote($additional['private_note']);
        }

        if (isset($additional['note']) && $additional['note']) {
            $invoice->setNote($additional['note']);
        }

        if (isset($additional['purchase_order_number']) && $additional['purchase_order_number']) {
            $invoice->setPurchaseOrderNumber($additional['purchase_order_number']);
        }

        if (isset($additional['language_id']) && $additional['language_id']) {
            $invoice->setLanguageId($additional['language_id']);
        }

        if (isset($additional['currency_id']) && $additional['currency_id']) {
            $invoice->setCurrencyId($additional['currency_id']);
        }

        return $invoice;
    }
}
