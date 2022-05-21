<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateStatusFieldUpdateForInvoices extends AngieModelMigration
{
    public function __construct()
    {
        $this->executeAfter('MigrateInvoicesToNewStorage');
    }

    public function up()
    {
        $invoices = $this->useTableForAlter('invoices');
        $invoices->addColumn(
            new DBBoolColumn('is_canceled'),
            'closed_by_email'
        );

        $this->execute('UPDATE ' . $invoices->getName() . ' SET is_canceled = ? WHERE status = ?', true, 3);

        $invoices->dropColumn('status');

        $this->doneUsingTables();
    }
}
