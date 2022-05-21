<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateInvoicesAndEstimatesTrashImplementation extends AngieModelMigration
{
    public function up()
    {
        $invoices = $this->useTableForAlter('invoices');
        $estimates = $this->useTableForAlter('estimates');

        $invoices->addColumn(new DBTrashColumn());
        $estimates->addColumn(new DBTrashColumn());

        $this->doneUsingTables();
    }
}
