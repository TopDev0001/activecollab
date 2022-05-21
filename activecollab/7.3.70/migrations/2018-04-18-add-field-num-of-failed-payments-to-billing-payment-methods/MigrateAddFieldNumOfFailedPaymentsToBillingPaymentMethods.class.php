<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddFieldNumOfFailedPaymentsToBillingPaymentMethods extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_payment_methods')) {
            $billing_payment_method = $this->useTableForAlter('billing_payment_methods');

            if (!$billing_payment_method->getColumn('num_of_failed_payments')) {
                $billing_payment_method
                    ->addColumn(
                        (new DBIntegerColumn('num_of_failed_payments', 1, 0))
                            ->setUnsigned(true)
                            ->setSize(DBColumn::TINY),
                        'zip_code'
                    );
            }
        }
    }
}
