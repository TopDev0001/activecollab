<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add updated on field to invoice model.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage migrations
 */
class MigrateUpdatedOnFieldForInvoices extends AngieModelMigration
{
    /**
     * Execute after.
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
        $invoices = $this->useTableForAlter('invoices');
        $invoices->addColumn(new DBUpdatedOnColumn(), 'created_by_email');

        $this->execute('UPDATE ' . $invoices->getName() . ' SET updated_on = created_on');
        $this->doneUsingTables();
    }
}
