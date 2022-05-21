<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddIs3dSecuredFieldToBillingPaymentMethod extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_payment_methods')) {
            $table = $this->useTableForAlter('billing_payment_methods');
            if (!$table->getColumn('is_3d_secured')) {
                $table->addColumn(
                    new DBBoolColumn('is_3d_secured'),
                    'num_of_failed_payments'
                );
            }

            $this->doneUsingTables();
        }
    }
}
