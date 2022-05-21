<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add foreign keys to invoice items table.
 *
 * @package ActiveCollab.migrations
 */
class MigrateFksForInvoiceItems extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $items = $this->useTableForAlter('invoice_items');

        $this->execute('UPDATE ' . $items->getName() . ' SET first_tax_rate_id = ? WHERE first_tax_rate_id IS NULL', 0);
        $this->execute('UPDATE ' . $items->getName() . ' SET second_tax_rate_id = ? WHERE second_tax_rate_id IS NULL', 0);

        $items->alterColumn('first_tax_rate_id', DBFkColumn::create('first_tax_rate_id'));
        $items->alterColumn('second_tax_rate_id', DBFkColumn::create('second_tax_rate_id'));

        $this->doneUsingTables();
    }
}
