<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateUpdateStatusOfTimeRecordsAndExpensesAttachedToCanceledQuickbooksInvoice extends AngieModelMigration
{
    public function up()
    {
        [$remote_invoices, $time_records, $expenses] = $this->useTables('remote_invoices', 'time_records', 'expenses');

        if ($rows = $this->execute("SELECT id, raw_additional_properties FROM $remote_invoices WHERE type = ? AND amount = ?", 'QuickbooksInvoice', 0)) {
            foreach ($rows as $row) {
                $id = $row['id'];
                $properties = unserialize($row['raw_additional_properties']);

                if (isset($properties['items']) && is_array($properties['items']) && !empty($properties['items'])) {
                    $time_record_ids = [];
                    $expense_ids = [];

                    foreach ($properties['items'] as $item) {
                        if (isset($item['time_record_ids']) && !empty($item['time_record_ids'])) {
                            $time_record_ids = array_merge($time_record_ids, $item['time_record_ids']);
                        }
                        if (isset($item['expense_ids']) && !empty($item['expense_ids'])) {
                            $expense_ids = array_merge($expense_ids, $item['expense_ids']);
                        }
                    }

                    $properties['items'] = [];

                    if (!empty($time_record_ids)) {
                        $this->execute("UPDATE $time_records SET billable_status = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?)", TimeRecord::BILLABLE, $time_record_ids);
                    }

                    if (!empty($expense_ids)) {
                        $this->execute("UPDATE $expenses SET billable_status = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?)", Expense::BILLABLE, $expense_ids);
                    }

                    $this->execute("UPDATE $remote_invoices SET raw_additional_properties = ? WHERE id = ?", serialize($properties), $id);
                }
            }
        }
    }
}
