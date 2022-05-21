<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate invoices to new storage.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage migrations
 */
class MigrateInvoicesToNewStorage extends AngieModelMigration
{
    /**
     * @var array
     */
    private $invoice_numbers = [];

    /**
     * Migrate up.
     */
    public function up()
    {
        if ($this->tableExists('invoices')) {
            $this->dropTable('invoices'); // Fix an issue caused by an old migration that did not correctly drop this table
        }

        $this->createTable(
            DB::createTable('invoices')->addColumns(
                [
                    new DBIdColumn(),
                    new DBRelatedObjectColumn('based_on', false),
                    DBStringColumn::create('number'),
                    DBStringColumn::create('purchase_order_number'),
                    DBIntegerColumn::create('company_id', 5, 0)->setUnsigned(true),
                    DBStringColumn::create('company_name', 255),
                    new DBTextColumn('company_address'),
                    DBIntegerColumn::create('currency_id', 4, 0)->setUnsigned(true),
                    DBIntegerColumn::create('language_id', 3, 0)->setUnsigned(true),
                    DBIntegerColumn::create('project_id', 5)->setUnsigned(true),
                    new DBMoneyColumn('subtotal', 0),
                    new DBMoneyColumn('tax', 0),
                    new DBMoneyColumn('total', 0),
                    new DBMoneyColumn('balance_due', 0),
                    new DBMoneyColumn('paid_amount', 0),
                    new DBTextColumn('note'),
                    DBStringColumn::create('private_note', 255),
                    DBIntegerColumn::create('status', 4, '0'),
                    DBIntegerColumn::create('allow_payments', 3, 1)->setUnsigned(true)->setSize(DBColumn::TINY),
                    new DBBoolColumn('second_tax_is_enabled'),
                    new DBBoolColumn('second_tax_is_compound'),
                    new DBCreatedOnByColumn(true),
                    new DBDateColumn('due_on'),
                    new DBDateColumn('issued_on'),
                    new DBDateTimeColumn('sent_on'),
                    new DBTextColumn('recipients'),
                    new DBUserColumn('email_from'),
                    DBStringColumn::create('email_subject'),
                    new DBTextColumn('email_body'),
                    new DBDateTimeColumn('reminder_sent_on'),
                    new DBDateColumn('closed_on'),
                    new DBUserColumn('closed_by'),
                    DBStringColumn::create('hash', 50),
                ]
            )->addIndices(
                [
                    DBIndex::create('number', DBIndex::UNIQUE),
                    DBIndex::create('company_id'),
                    DBIndex::create('project_id'),
                    DBIndex::create('total'),
                    DBIndex::create('issued_on'),
                    DBIndex::create('due_on'),
                    DBIndex::create('sent_on'),
                    DBIndex::create('closed_on'),
                ]
            )
        );

        [$invoices, $invoice_objects, $invoice_object_items, $invoice_related_records, $companies, $time_records, $expenses] = $this->useTables('invoices', 'invoice_objects', 'invoice_object_items', 'invoice_related_records', 'companies', 'time_records', 'expenses');

        $company_info = [];

        if ($rows = $this->execute("SELECT id, name, address FROM $companies WHERE id IN (SELECT DISTINCT company_id AS 'id' FROM $invoice_objects WHERE type = 'Invoice' AND status > ?)", 0)) {
            foreach ($rows as $row) {
                $company_info[$row['id']] = ['name' => $row['name'], 'address' => $row['address']];
            }
        }

        $this->execute("UPDATE $invoice_objects SET recipient_name = '' WHERE recipient_name IS NULL");
        $this->execute("UPDATE $invoice_objects SET recipient_email = '' WHERE recipient_email IS NULL");

        if ($rows = $this->execute("SELECT id, based_on_type, based_on_id, varchar_field_1 AS 'number', varchar_field_2 AS 'purchase_order_number', company_id, company_name, company_address,
        currency_id, language_id, project_id, subtotal, tax, total, balance_due, paid_amount, note, private_note, status, allow_payments, second_tax_is_enabled, second_tax_is_compound,
        created_on, created_by_id, created_by_name, created_by_email, date_field_1 AS 'due_on', date_field_2 AS 'issued_on',
        date_field_2 AS 'sent_on', TRIM(CONCAT(recipient_name, ' <', recipient_email, '>')) AS 'recipients', integer_field_1 AS 'email_from_id', varchar_field_3 AS 'email_from_name', varchar_field_4 AS 'email_from_email', '' AS 'email_subject', '' AS 'email_body',
        reminder_sent_on, DATE(closed_on) AS 'closed_on', closed_by_id, closed_by_name, closed_by_email, hash FROM $invoice_objects WHERE type = 'Invoice' AND status > ?", 0)
        ) {
            $batch = new DBBatchInsert($invoices, DB::listTableFields($invoices));

            $default_allow_payments = (int) $this->getConfigOptionValue('allow_payments_for_invoice');

            if ($default_allow_payments < 1) {
                $default_allow_payments = 0;
            }

            if ($default_allow_payments > 2) {
                $default_allow_payments = 2;
            }

            foreach ($rows as $row) {
                $this->ensureUniqueInvoiceNumber($row);

                if (empty($row['purchase_order_number'])) {
                    $row['purchase_order_number'] = null;
                }

                if (empty($row['company_name']) && isset($company_info[$row['company_id']])) {
                    $row['company_name'] = $company_info[$row['company_id']]['name'];
                }

                if (empty($row['company_address']) && isset($company_info[$row['company_id']])) {
                    $row['company_address'] = $company_info[$row['company_id']]['address'];
                }

                if (trim($row['recipients']) == '<>' || empty($row['recipients'])) {
                    $row['recipients'] = null;
                } elseif (str_starts_with($row['recipients'], '<') && str_ends_with($row['recipients'], '>')) {
                    $row['recipients'] = substr($row['recipients'], 1, strlen($row['recipients']) - 2);
                }

                $row['allow_payments'] = (int) $row['allow_payments'];

                if ($row['allow_payments'] < 0) {
                    $row['allow_payments'] = $default_allow_payments;
                }

                if ($row['private_note'] && mb_strlen($row['private_note']) > 191) {
                    $row['private_note'] = mb_substr($row['private_note'], 0, 191);
                }

                $batch->insertArray($row);
            }

            $batch->done();
        }

        if ($draft_invoice_ids = $this->executeFirstColumn("SELECT id FROM $invoice_objects WHERE status = ?", 0)) {
            $this->execute("DELETE FROM $invoice_object_items WHERE parent_type = 'Invoice' AND parent_id IN (?)", $draft_invoice_ids);

            // Release time records and expenses that were connected to draft invoices
            $this->execute("UPDATE $time_records SET billable_status = ? WHERE billable_status = ? AND id IN (SELECT DISTINCT parent_id FROM $invoice_related_records WHERE parent_type = 'TimeRecord' AND invoice_id IN (?))", 1, 2, $draft_invoice_ids);
            $this->execute("UPDATE $expenses SET billable_status = ? WHERE billable_status = ? AND id IN (SELECT DISTINCT parent_id FROM $invoice_related_records WHERE parent_type = 'Expense' AND invoice_id IN (?))", 1, 2, $draft_invoice_ids);

            $this->execute("DELETE FROM $invoice_related_records WHERE invoice_id IN (?)", $draft_invoice_ids);
        }

        $this->doneUsingTables();
    }

    /**
     * Make sure that we have a valid, unique invoice number in $row.
     */
    private function ensureUniqueInvoiceNumber(array &$row)
    {
        if (in_array($row['number'], $this->invoice_numbers)) {
            $counter = 1;

            do {
                $new_invoice_number = $row['number'] . '-' . $counter++;
            } while (in_array($new_invoice_number, $this->invoice_numbers));

            $row['number'] = $new_invoice_number;
        }

        $this->invoice_numbers[] = $row['number'];
    }
}
