<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Update discount, first_tax, second_tax and total columns.
 *
 * @package angie.migrations
 */
class MigrateUpdateDiscountRateInvoiceModel extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $invoice = $this->useTableForAlter('invoices');
        $invoice_items = $this->useTableForAlter('invoice_items');

        $invoice->alterColumn('discount_rate', DBDecimalColumn::create('discount_rate', 5, 2, 0)->setUnsigned(true));
        $invoice_items->alterColumn('discount_rate', DBDecimalColumn::create('discount_rate', 5, 2, 0)->setUnsigned(true));

        $this->doneUsingTables();
    }
}
