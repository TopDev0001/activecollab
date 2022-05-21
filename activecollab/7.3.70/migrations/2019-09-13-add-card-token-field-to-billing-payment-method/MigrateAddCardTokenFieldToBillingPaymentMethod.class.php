<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddCardTokenFieldToBillingPaymentMethod extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_payment_methods')) {
            $billing_payment_methods = $this->useTableForAlter('billing_payment_methods');

            if (!$billing_payment_methods->getColumn('card_token')) {
                $billing_payment_methods->addColumn(
                    new DBStringColumn(
                        'card_token'
                    ),
                    'token'
                );
            }

            $this->doneUsingTables();
        }
    }
}
