<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddBillingOrdersTable extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('billing_orders')) {
            $this->createTable('billing_orders', [
                new DBIdColumn(),
                new DBTypeColumn('BillingOrder'),
                new DBEnumColumn(
                    'status',
                    [
                        'pending',
                        'success',
                        'failed',
                    ],
                    'pending'
                ),
                DBStringColumn::create('plan', 50),
                DBIntegerColumn::create('number_of_members', 10, 1)->setUnsigned(true),
                new DBEnumColumn(
                    'billing_period',
                    [
                        'monthly',
                        'annually',
                    ],
                    'annually'
                ),
                DBStringColumn::create('tax_name'),
                DBDecimalColumn::create('tax_rate', 4, 2, 0)->setUnsigned(true),
                DBStringColumn::create('code', 50),
                new DBMoneyColumn('subtotal', 0),
                new DBMoneyColumn('tax', 0),
                new DBMoneyColumn('total', 0),
                new DBEnumColumn('initial_billing_period', ['monthly', 'annually'], 'annually'),
                new DBEnumColumn('step', ['billing_address', 'payment_method']),
                new DBCreatedOnByColumn(),
                new DBUpdatedOnColumn(),
            ],
            [
                DBIndex::create('code', DBIndex::UNIQUE),
            ]);
        }
    }
}
