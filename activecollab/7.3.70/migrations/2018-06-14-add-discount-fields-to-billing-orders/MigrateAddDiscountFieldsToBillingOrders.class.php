<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddDiscountFieldsToBillingOrders extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_orders')) {
            $billing_orders = $this->useTableForAlter('billing_orders');

            if (!$billing_orders->getColumn('discount_rate')) {
                $billing_orders->addColumn(
                    new DBIntegerColumn('discount_rate', 3, 0),
                    'tax_rate'
                );
            }
            if (!$billing_orders->getColumn('discount')) {
                $billing_orders->addColumn(
                    new DBMoneyColumn('discount', 0),
                    'subtotal'
                );
            }

            $this->doneUsingTables();
        }
    }
}
