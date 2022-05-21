<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddBillingOrderItemTotalColumn extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_order_items')) {
            $billing_order_items = $this->useTableForAlter('billing_order_items');

            if (!$billing_order_items->getColumn('total')) {
                $billing_order_items->addColumn(
                    new DBMoneyColumn('total', 0),
                    'billing_balance_record_id'
                );
            }

            $this->doneUsingTables();
        }
    }
}
