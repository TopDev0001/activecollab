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
class MigrateUpdateInvoiceItemsModel extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $invoice_items = $this->useTableForAlter('invoice_items');

        $invoice_items->alterColumn('discount', DBDecimalColumn::create('discount', 13, 5, 0));
        $invoice_items->alterColumn('first_tax', DBDecimalColumn::create('first_tax', 13, 5, 0));
        $invoice_items->alterColumn('second_tax', DBDecimalColumn::create('second_tax', 13, 5, 0));
        $invoice_items->alterColumn('total', DBDecimalColumn::create('total', 13, 5, 0));

        $this->doneUsingTables();
    }
}
