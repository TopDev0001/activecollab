<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddOrderSummaryFieldToBillingOrdersTable extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_orders')) {
            $billing_orders = $this->useTableForAlter('billing_orders');

            if (!$billing_orders->getColumn('order_summary')) {
                $billing_orders->addColumn(new DBStringColumn('order_summary', 255), 'plan');
            }

            $this->doneUsingTables();
        }
    }
}
