<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateRemoteInvoiceItemsInNewFormat extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('remote_invoices')) {
            $result = DB::execute('SELECT id, type, raw_additional_properties, updated_by_id, updated_by_name, updated_by_email FROM remote_invoices WHERE based_on = "time_and_expenses"');
            if ($result) {
                $remote_invoices = $result->toArray();
                foreach ($remote_invoices as $remote_invoice) {
                    if (array_key_exists('raw_additional_properties', $remote_invoice) && !empty($remote_invoice['raw_additional_properties'])) {
                        $properties = unserialize($remote_invoice['raw_additional_properties']);
                        if ($properties && array_key_exists('items', $properties)) {
                            $items = $properties['items'];
                            if ($items) {
                                foreach ($items as $item) {
                                    $time_record_ids = array_key_exists('time_record_ids', $item) ? $item['time_record_ids'] : [];
                                    $expense_ids = array_key_exists('expense_ids', $item) ? $item['expense_ids'] : [];
                                    $line_id = array_key_exists('line_id', $item) ? $item['line_id'] : '';

                                    $already_existing_remote_invoice_item = RemoteInvoiceItems::findOneBy([
                                        'parent_type' => $remote_invoice['type'],
                                        'parent_id' => $remote_invoice['id'],
                                        'line_id_string' => $line_id,
                                    ]);

                                    if ($already_existing_remote_invoice_item) {
                                        $already_existing_remote_invoice_item->delete();
                                    }

                                    $remote_invoice_item = RemoteInvoiceItems::create([
                                        'parent_type' => $remote_invoice['type'],
                                        'parent_id' => $remote_invoice['id'],
                                        'line_id_string' => $line_id,
                                        'updated_by_id' => $remote_invoice['updated_by_id'],
                                        'updated_by_name' => $remote_invoice['updated_by_name'],
                                        'updated_by_email' => $remote_invoice['updated_by_email'],
                                    ]);

                                    if (count($time_record_ids)) {
                                        DB::execute('UPDATE time_records SET invoice_item_id = ?, invoice_type = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?)', $remote_invoice_item->getId(), 'remote', $time_record_ids);
                                    }

                                    if (count($expense_ids)) {
                                        DB::execute('UPDATE expenses SET invoice_item_id = ?, invoice_type = ?, updated_on = UTC_TIMESTAMP() WHERE id IN (?)', $remote_invoice_item->getId(), 'remote', $expense_ids);
                                    }
                                }
                            }
                        }

                        if (array_key_exists('items', $properties)) {
                            unset($properties['items']);
                        }

                        DB::execute('UPDATE remote_invoices SET raw_additional_properties = ? WHERE id = ?', serialize($properties), $remote_invoice['id']);
                    }
                }
            }
        }
    }
}
