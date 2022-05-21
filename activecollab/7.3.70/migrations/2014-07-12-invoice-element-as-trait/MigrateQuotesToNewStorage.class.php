<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate storage to the new storage.
 *
 * @package ActiveCollab.modules.invoicing
 * @subpackage migrations
 */
class MigrateQuotesToNewStorage extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        if ($this->tableExists('quotes')) {
            $this->dropTable('quotes');  // Fix an issue caused by an old migration that did not correctly drop this table
        }

        $this->createTable(
            DB::createTable('quotes')->addColumns(
                [
                    new DBIdColumn(),
                    new DBNameColumn(),
                    DBIntegerColumn::create('company_id', 5, 0)->setUnsigned(true),
                    DBStringColumn::create('company_name', 255),
                    new DBTextColumn('company_address'),
                    DBIntegerColumn::create('currency_id', 4, 0)->setUnsigned(true),
                    DBIntegerColumn::create('language_id', 3, 0)->setUnsigned(true),
                    new DBMoneyColumn('subtotal', 0),
                    new DBMoneyColumn('tax', 0),
                    new DBMoneyColumn('total', 0),
                    new DBMoneyColumn('balance_due', 0),
                    new DBMoneyColumn('paid_amount', 0),
                    new DBTextColumn('note'),
                    DBStringColumn::create('private_note', 255),
                    new DBEnumColumn('status', ['draft', 'sent', 'won', 'lost'], 'draft'),
                    new DBBoolColumn('second_tax_is_enabled', false),
                    new DBBoolColumn('second_tax_is_compound', false),
                    new DBTextColumn('recipients'),
                    new DBUserColumn('email_from'),
                    DBStringColumn::create('email_subject'),
                    new DBTextColumn('email_body'),
                    new DBCreatedOnByColumn(true),
                    new DBActionOnByColumn('sent', true),
                    DBStringColumn::create('hash', 50),
                ]
            )->addIndices(
                [
                    DBIndex::create('company_id'),
                    DBIndex::create('status'),
                    DBIndex::create('sent_on'),
                    DBIndex::create('hash', DBIndex::UNIQUE),
                ]
            )
        );

        [$quotes, $invoice_objects, $companies] = $this->useTables('quotes', 'invoice_objects', 'companies');

        $company_info = [];

        if ($rows = $this->execute("SELECT id, name, address FROM $companies WHERE id IN (SELECT DISTINCT company_id AS 'id' FROM $invoice_objects WHERE type = 'Quote')")) {
            foreach ($rows as $row) {
                $company_info[$row['id']] = ['name' => $row['name'], 'address' => $row['address']];
            }
        }

        if ($rows = $this->execute("SELECT id, name, company_id, company_name, company_address, currency_id, language_id,
        subtotal, tax, total, balance_due, paid_amount, note, private_note, status, second_tax_is_enabled, second_tax_is_compound,
        CONCAT(recipient_name, ' <', recipient_email, '>') AS 'recipients', sent_by_id AS 'email_from_id', sent_by_name AS 'email_from_name', sent_by_email AS 'email_from_email', '' AS 'email_subject', '' AS 'email_body',
        created_on, created_by_id, created_by_name, created_by_email, sent_on, sent_by_id, sent_by_name, sent_by_email, hash FROM $invoice_objects WHERE type = 'Quote'")
        ) {
            $batch = new DBBatchInsert($quotes, DB::listTableFields($quotes));

            $hashes = [];

            foreach ($rows as $row) {
                if (empty($row['company_name']) && isset($company_info[$row['company_id']])) {
                    $row['company_name'] = $company_info[$row['company_id']]['name'];
                }

                if (empty($row['company_address']) && isset($company_info[$row['company_id']])) {
                    $row['company_address'] = $company_info[$row['company_id']]['address'];
                }

                if (trim($row['recipients']) == '<>') {
                    $row['recipients'] = '';
                }

                while (empty($row['hash']) || strlen($row['hash'] < 40) || in_array($row['hash'], $hashes)) {
                    $row['hash'] = make_string(40);
                }

                $row['private_note'] = mb_substr($row['private_note'], 0, 191);

                $hashes[] = $row['hash'];

                if (empty($row['name'])) {
                    $row['name'] = 'Quote #' . strtoupper(substr($row['hash'], 0, 5)) . '-' . $row['id'];
                }

                switch ($row['status']) {
                    case 1:
                        $row['status'] = 'sent';
                        break;
                    case 2:
                        $row['status'] = 'won';
                        break;
                    case 3:
                        $row['status'] = 'lost';
                        break;
                    default:
                        $row['status'] = 'draft';
                }

                $batch->insertArray($row);
            }

            $batch->done();
        }

        $this->doneUsingTables();
    }
}
