<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddTableBillingPaymentMethods extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('billing_payment_methods')) {
            $this->createTable(
                DB::createTable('billing_payment_methods')->addColumns(
                    [
                        new DBIdColumn(),
                        new DBTypeColumn(),
                        DBStringColumn::create('card_type', 100),
                        DBStringColumn::create('card_number', 100),
                        DBIntegerColumn::create('expiration_month', 3)->setUnsigned(true)->setSize(DBColumn::TINY),
                        DBIntegerColumn::create('expiration_year', 5)->setUnsigned(true)->setSize(DBColumn::SMALL),
                        DBStringColumn::create('token', DBStringColumn::MAX_LENGTH),
                        DBStringColumn::create('name', DBStringColumn::MAX_LENGTH),
                        DBStringColumn::create('email', DBStringColumn::MAX_LENGTH),
                        DBStringColumn::create('vat', 100),
                        DBStringColumn::create('country', DBStringColumn::MAX_LENGTH),
                        DBStringColumn::create('address', DBStringColumn::MAX_LENGTH),
                        DBStringColumn::create('address_extended', DBStringColumn::MAX_LENGTH),
                        DBStringColumn::create('city', DBStringColumn::MAX_LENGTH),
                        DBStringColumn::create('region', DBStringColumn::MAX_LENGTH),
                        DBStringColumn::create('zip_code', 100),
                        new DBAdditionalPropertiesColumn(),
                        new DBUpdatedOnColumn(),
                    ]
                )
            );
        }
    }
}
