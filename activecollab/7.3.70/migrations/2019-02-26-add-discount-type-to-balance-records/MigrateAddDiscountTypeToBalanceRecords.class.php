<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddDiscountTypeToBalanceRecords extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_balance_records')) {
            $billing_balance_records = $this->useTableForAlter('billing_balance_records');
            $billing_balance_records->getColumn('balance_type');
            $billing_balance_records->alterColumn(
                'balance_type',
                new DBEnumColumn(
                    'balance_type',
                    [
                        'subscription_fee',
                        'seat_fee',
                        'failed_payment_active_days_fee',
                        'account_balance_charge',
                        'account_balance_discount',
                    ],
                    null
                ), 'id'
            );

            $this->doneUsingTables();
        }
    }
}
