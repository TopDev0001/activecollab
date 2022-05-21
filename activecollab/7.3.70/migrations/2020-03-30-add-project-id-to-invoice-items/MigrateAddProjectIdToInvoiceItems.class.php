<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddProjectIdToInvoiceItems extends AngieModelMigration
{
    public function up()
    {
        $invoice_items = $this->useTableForAlter('invoice_items');

        if (!$invoice_items->getColumn('project_id')) {
            $invoice_items->addColumn(
                DBIntegerColumn::create('project_id', 10, 0)->setUnsigned(true)
            );
        }
    }
}
