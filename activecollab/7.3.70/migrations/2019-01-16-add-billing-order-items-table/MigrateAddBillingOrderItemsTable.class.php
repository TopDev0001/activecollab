<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddBillingOrderItemsTable extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('billing_order_items')) {
            $this->createTable(
                DB::createTable('billing_order_items')->addColumns(
                    [
                        new DBIdColumn(),
                        new DBTypeColumn(
                            'ActiveCollab\Module\OnDemand\Model\BillingOrderItem\SubscriptionFeeBillingOrderItem'
                        ),
                        DBIntegerColumn::create('billing_order_id')->setUnsigned(true),
                        DBIntegerColumn::create('billing_balance_record_id', 5, null),
                    ]
                )
            );
        }
    }
}
