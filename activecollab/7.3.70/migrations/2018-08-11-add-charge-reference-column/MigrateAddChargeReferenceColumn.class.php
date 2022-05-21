<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddChargeReferenceColumn extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_orders')) {
            $billing_orders = $this->useTableForAlter('billing_orders');

            if (!$billing_orders->getColumn('charge_reference')) {
                $billing_orders->addColumn(
                    DBStringColumn::create('charge_reference', 50),
                    'thank_you'
                );
            }
        }
    }
}
