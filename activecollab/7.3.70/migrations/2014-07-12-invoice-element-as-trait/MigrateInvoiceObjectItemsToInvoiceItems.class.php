<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate invoice object items to invoice items.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage migrations
 */
class MigrateInvoiceObjectItemsToInvoiceItems extends AngieModelMigration
{
    /**
     * Prepare migration.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateInvoicesToNewStorage');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        if ($this->tableExists('invoice_items')) {
            $this->dropTable('invoice_items'); // Fix an issue caused by an old migration that did not correctly drop this table
        }

        $invoice_object_items = $this->useTableForAlter('invoice_object_items');

        $this->execute('UPDATE ' . $invoice_object_items->getName() . ' SET position = ? WHERE position < ?', 1, 2);

        $invoice_object_items->dropColumn('type');
        $invoice_object_items->alterColumn('position', DBIntegerColumn::create('position', 11)->setUnsigned(true));

        $invoice_object_items->dropIndex('parent');
        $invoice_object_items->dropIndex('parent_id');

        $invoice_object_items->addIndex(DBIndex::create('parent', DBIndex::KEY, ['parent_id', 'parent_type', 'position']));

        $this->doneUsingTables();

        $this->renameTable('invoice_object_items', 'invoice_items');
    }
}
