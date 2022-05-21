<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add discount rate.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage migrations
 */
class MigrateAddDiscountRate extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $invoices = $this->useTableForAlter('invoices');
        $recurring_profiles = $this->useTableForAlter('recurring_profiles');
        $quotes = $this->useTableForAlter('quotes');
        $invoice_items = $this->useTableForAlter('invoice_items');

        /** @var DBTable $table */
        foreach ([$invoices, $recurring_profiles] as $table) {
            $table->addColumn(DBIntegerColumn::create('discount_rate', DBColumn::TINY, 0)->setUnsigned(true), 'project_id');
        }

        $quotes->addColumn(DBIntegerColumn::create('discount_rate', DBColumn::TINY, 0)->setUnsigned(true), 'langauge_id');
        $invoice_items->addColumn(DBIntegerColumn::create('discount_rate', DBColumn::TINY, 0)->setUnsigned(true), 'second_tax_rate_id');

        foreach ([$invoices, $recurring_profiles, $quotes, $invoice_items] as $table) {
            $table->addColumn(new DBMoneyColumn('discount', 0), 'subtotal');
        }
    }
}
