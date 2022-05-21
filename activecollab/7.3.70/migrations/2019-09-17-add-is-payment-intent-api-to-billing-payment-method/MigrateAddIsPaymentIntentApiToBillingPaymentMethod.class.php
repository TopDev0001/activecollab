<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddIsPaymentIntentApiToBillingPaymentMethod extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_payment_methods')) {
            $table = $this->useTableForAlter('billing_payment_methods');
            if (!$table->getColumn('is_payment_intent_api')) {
                $table->addColumn(
                    new DBBoolColumn('is_payment_intent_api'),
                    'num_of_failed_payments'
                );
            }

            $this->doneUsingTables();
        }
    }
}
