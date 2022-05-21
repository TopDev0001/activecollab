<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddInvoiceTypeColumn extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('time_records')) {
            $time_records = $this->useTableForAlter('time_records');
            if (!$time_records->getColumn('invoice_type')) {
                $time_records->addColumn(
                    new DBEnumColumn('invoice_type', ['local', 'remote']),
                    'invoice_item_id'
                );
            }
        }

        if ($this->tableExists('expenses')) {
            $expenses = $this->useTableForAlter('expenses');
            if (!$expenses->getColumn('invoice_type')) {
                $expenses->addColumn(
                    new DBEnumColumn('invoice_type', ['local', 'remote']),
                    'invoice_item_id'
                );
            }
        }

        $this->execute('UPDATE time_records SET invoice_type = "local" WHERE invoice_item_id > 0');
        $this->execute('UPDATE expenses SET invoice_type = "local" WHERE invoice_item_id > 0');

        $this->doneUsingTables();
    }
}
