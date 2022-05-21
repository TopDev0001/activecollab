<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateMarkTimeRecordsAndExpensesAsPaidForRemoteInvoices extends AngieModelMigration
{
    public function up()
    {
        $invoices_table = $this->useTables('remote_invoices')[0];
        $invoice_items_table = $this->useTables('remote_invoice_items')[0];

        $paid_invoice_items_ids = $this->executeFirstColumn("SELECT DISTINCT iit.id FROM $invoice_items_table as iit JOIN $invoices_table it ON iit.parent_id = it.id WHERE it.updated_on >= ? AND it.amount != it.balance", (new DateTimeValue('2020-08-20'))->toMySQL());

        $tables_to_update = [
            'time_records',
            'expenses',
        ];

        foreach ($tables_to_update as $table_to_update) {
            if ($this->tableExists($table_to_update)) {
                $table = $this->useTableForAlter($table_to_update);

                $this->execute('UPDATE ' . $table->getName() . " SET billable_status = 3, updated_on = ? WHERE invoice_type = 'remote' AND invoice_item_id IN (?)", DateTimeValue::now()->toMySQL(), $paid_invoice_items_ids);
            }
        }

        $this->doneUsingTables();
    }
}
