<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Clean-up after Feather refactoring of invoicing model.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage migrations
 */
class MigrateFeatherInvoicingRefactoringCleanUp extends AngieModelMigration
{
    /**
     * Execute after.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateInvoicesToNewStorage', 'MigrateInvoiceObjectItemsToInvoiceItems', 'MigrateRecurringProfilesToNewStorage', 'MigrateQuotesToNewStorage');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        $this->dropTable('invoice_objects');
        $this->dropTable('invoice_object_items');
    }
}
