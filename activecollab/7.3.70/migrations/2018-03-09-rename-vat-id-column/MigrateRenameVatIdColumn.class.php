<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateRenameVatIdColumn extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_payment_methods')) {
            $billing_payment_methods = $this->useTableForAlter('billing_payment_methods');

            if ($billing_payment_methods->getColumn('vat_id')) {
                $this->execute('ALTER TABLE billing_payment_methods CHANGE `vat_id` `vat` VARCHAR(100)');
            }

            $this->doneUsingTables();
        }
    }
}
