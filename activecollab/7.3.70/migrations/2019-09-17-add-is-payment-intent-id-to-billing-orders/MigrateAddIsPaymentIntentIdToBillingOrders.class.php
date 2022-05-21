<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddIsPaymentIntentIdToBillingOrders extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_orders')) {
            $table = $this->useTableForAlter('billing_orders');
            if (!$table->getColumn('payment_intent_id')) {
                $table->addColumn(DBStringColumn::create('payment_intent_id', 50), 'is_changing_payment_gateway');
            }

            $this->doneUsingTables();
        }
    }
}
