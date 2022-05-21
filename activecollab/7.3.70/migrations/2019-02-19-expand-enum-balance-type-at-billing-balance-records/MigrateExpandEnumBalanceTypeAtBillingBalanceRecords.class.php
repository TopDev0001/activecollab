<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateExpandEnumBalanceTypeAtBillingBalanceRecords extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_balance_records')) {
            $billing_balance_records = $this->useTableForAlter('billing_balance_records');
            $billing_balance_records->getColumn('balance_type');
            $billing_balance_records->alterColumn('balance_type',
                new DBEnumColumn(
                    'balance_type',
                    [
                        'subscription_fee',
                        'seat_fee',
                        'failed_payment_active_days_fee',
                        'account_balance_charge',
                    ],
                    null
                ),
                'id'
            );

            $this->doneUsingTables();
        }
    }
}
