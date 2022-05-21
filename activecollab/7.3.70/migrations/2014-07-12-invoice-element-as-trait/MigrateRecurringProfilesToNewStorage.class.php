<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate recurring profiles to the new storage.
 *
 * @package ActiveCollab.modules.invoicing
 * @subpackage migrations
 */
class MigrateRecurringProfilesToNewStorage extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        if ($this->tableExists('recurring_profiles')) {
            $this->dropTable('recurring_profiles');  // Fix an issue caused by an old migration that did not correctly drop this table
        }

        $this->createTable(
            DB::createTable('recurring_profiles')->addColumns(
                [
                    new DBIdColumn(),
                    new DBNameColumn(),
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
                    new DBBoolColumn('second_tax_is_enabled', false),
                    new DBBoolColumn('second_tax_is_compound', false),
                    new DBCreatedOnByColumn(true),
                    new DBDateColumn('start_on'),
                    DBIntegerColumn::create('invoice_due_after', DBColumn::NORMAL, 15)->setUnsigned(true),
                    new DBEnumColumn('frequency', ['daily', 'weekly', 'biweekly', 'monthly', 'bimonthly', 'quarterly', 'halfyearly', 'yearly', 'biannual'], 'monthly'),
                    DBIntegerColumn::create('occurrences', DBColumn::NORMAL, 0)->setUnsigned(true),
                    DBIntegerColumn::create('triggered_number', DBColumn::NORMAL, 0)->setUnsigned(true),
                    new DBDateColumn('last_triggered_on'),
                    new DBDateColumn('next_trigger_on'),
                    new DBBoolColumn('auto_issue', false),
                    new DBTextColumn('recipients'),
                    DBIntegerColumn::create('email_from_id', DBColumn::NORMAL, 0)->setUnsigned(true),
                    DBStringColumn::create('email_subject'),
                    new DBTextColumn('email_body'),
                    DBIntegerColumn::create('allow_payments', 3, 1)->setUnsigned(true)->setSize(DBColumn::TINY),
                    new DBBoolColumn('is_enabled'),
                ]
            )->addIndices(
                [
                    DBIndex::create('start_on'),
                    DBIndex::create('next_trigger_on'),
                ]
            )
        );

        [$recurring_profiles, $invoice_objects, $companies] = $this->useTables('recurring_profiles', 'invoice_objects', 'companies');

        $company_info = [];

        if ($rows = $this->execute("SELECT id, name, address FROM $companies WHERE id IN (SELECT DISTINCT company_id AS 'id' FROM $invoice_objects WHERE type = 'RecurringProfile')", 0)) {
            foreach ($rows as $row) {
                $company_info[$row['id']] = ['name' => $row['name'], 'address' => $row['address']];
            }
        }

        if ($rows = $this->execute("SELECT id, name, varchar_field_2 AS 'purchase_order_number', company_id, company_name, company_address, currency_id, language_id, project_id,
        subtotal, tax, total, balance_due, paid_amount, note, private_note, second_tax_is_enabled, second_tax_is_compound, created_on, created_by_id, created_by_name, created_by_email,
        date_field_3 AS 'start_on', integer_field_2 AS 'invoice_due_after', varchar_field_1 AS 'frequency', varchar_field_2 AS 'occurrences', integer_field_3 AS 'triggered_number',
        date_field_1 AS 'last_triggered_on', date_field_2 AS 'next_trigger_on', integer_field_1 AS 'auto_issue',
        '' AS 'recipients', 0 AS 'email_from_id', '' AS email_subject, '' AS 'email_body', allow_payments, 0 AS 'is_enabled' FROM $invoice_objects WHERE type = 'RecurringProfile'")
        ) {
            $batch = new DBBatchInsert($recurring_profiles, DB::listTableFields($recurring_profiles));

            $default_allow_payments = (int) $this->getConfigOptionValue('allow_payments_for_invoice');

            if ($default_allow_payments < 1) {
                $default_allow_payments = 0;
            }

            if ($default_allow_payments > 2) {
                $default_allow_payments = 2;
            }

            foreach ($rows as $row) {
                if (empty($row['purchase_order_number'])) {
                    $row['purchase_order_number'] = null;
                }

                if (empty($row['company_name']) && isset($company_info[$row['company_id']])) {
                    $row['company_name'] = $company_info[$row['company_id']]['name'];
                }

                if (empty($row['company_address']) && isset($company_info[$row['company_id']])) {
                    $row['company_address'] = $company_info[$row['company_id']]['address'];
                }

                if ($row['frequency'] == '2 weeks') {
                    $row['frequency'] = 'biweekly';
                } else {
                    if ($row['frequency'] == '2 months') {
                        $row['frequency'] = 'bimonthly';
                    } else {
                        if ($row['frequency'] == '3 months') {
                            $row['frequency'] = 'quarterly';
                        } else {
                            if ($row['frequency'] == '6 months') {
                                $row['frequency'] = 'halfyearly';
                            }
                        }
                    }
                }

                if (empty($row['triggered_number'])) {
                    $row['triggered_number'] = 0;
                }

                switch ($row['company_address']) {
                    case '2 weeks':
                        $row['company_address'] = 'biweekly';
                        break;
                    case '2 months':
                        $row['company_address'] = 'bimonthly';
                        break;
                    case '3 months':
                        $row['company_address'] = 'quarterly';
                        break;
                    case '6 months':
                        $row['company_address'] = 'halfyearly';
                        break;
                }

                if ($row['allow_payments'] === null || $row['allow_payments'] < 0) {
                    $row['allow_payments'] = $default_allow_payments;
                }

                $batch->insertArray($row);
            }

            $batch->done();
        }

        $this->doneUsingTables();
    }
}
