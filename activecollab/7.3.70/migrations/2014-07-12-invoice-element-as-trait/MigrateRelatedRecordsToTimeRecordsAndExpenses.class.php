<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate related records to time records and expenses table.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage migrations
 */
class MigrateRelatedRecordsToTimeRecordsAndExpenses extends AngieModelMigration
{
    /**
     * Prepare migration.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateInvoiceObjectItemsToInvoiceItems');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        $time_records = $this->tableExists('time_records') ? $this->useTableForAlter('time_records') : null;
        $expenses = $this->tableExists('expenses') ? $this->useTableForAlter('expenses') : null;

        if ($time_records) {
            $time_records->addColumn(DBFkColumn::create('invoice_item_id', 0, true), 'parent_id');
            $time_records->addIndex(DBIndex::create('invoice_item_id'));
        }

        if ($expenses) {
            $expenses->addColumn(DBFkColumn::create('invoice_item_id', 0, true), 'parent_id');
            $expenses->addIndex(DBIndex::create('invoice_item_id'));
        }

        [$invoice_items, $related_records] = $this->useTables('invoice_items', 'invoice_related_records');

        if ($rows = $this->execute("SELECT $related_records.* FROM $related_records LEFT JOIN $invoice_items ON $related_records.item_id = $invoice_items.id")) {
            $records = [];

            foreach ($rows as $row) {
                $invoice_item_id = $row['item_id'];

                if (empty($records[$invoice_item_id])) {
                    $records[$invoice_item_id] = ['time_record_ids' => [], 'expense_ids' => []];
                }

                if (strtolower($row['parent_type']) === 'timerecord') {
                    $records[$invoice_item_id]['time_record_ids'][] = $row['parent_id'];
                } elseif (strtolower($row['parent_type']) === 'expense') {
                    $records[$invoice_item_id]['expense_ids'][] = $row['parent_id'];
                }
            }

            foreach ($records as $invoice_item_id => $record_ids) {
                if ($time_records && is_foreachable($record_ids['time_record_ids'])) {
                    $this->execute('UPDATE ' . $time_records->getName() . ' SET invoice_item_id = ? WHERE id IN (?)', $invoice_item_id, $record_ids['time_record_ids']);
                }

                if ($expenses && is_foreachable($record_ids['expense_ids'])) {
                    $this->execute('UPDATE ' . $expenses->getName() . ' SET invoice_item_id = ? WHERE id IN (?)', $invoice_item_id, $record_ids['expense_ids']);
                }
            }
        }

        $this->dropTable('invoice_related_records');
    }
}
