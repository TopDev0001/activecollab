<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddAddonsColumnToBillingOrders extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_orders')) {
            $billing_orders = $this->useTableForAlter('billing_orders');

            if (!$billing_orders->getColumn('add_ons')) {
                $billing_orders->addColumn(new DBTextColumn('add_ons'), 'plan');
            }

            $this->doneUsingTables();
        }
    }
}
