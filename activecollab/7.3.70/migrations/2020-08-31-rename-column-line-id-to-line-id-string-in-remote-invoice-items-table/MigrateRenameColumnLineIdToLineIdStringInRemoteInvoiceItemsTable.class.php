<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateRenameColumnLineIdToLineIdStringInRemoteInvoiceItemsTable extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('remote_invoice_items')) {
            $remote_invoice_items = $this->useTableForAlter('remote_invoice_items');

            if ($remote_invoice_items->getColumn('line_id')) {
                $this->execute("ALTER TABLE {$remote_invoice_items->getName()} CHANGE COLUMN line_id line_id_string VARCHAR(50)");
            }
        }
    }
}
