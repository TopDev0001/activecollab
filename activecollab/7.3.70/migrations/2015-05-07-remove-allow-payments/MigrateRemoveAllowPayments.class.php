<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrare allow payments.
 *
 * @package ActiveCollab.migrations
 */
class MigrateRemoveAllowPayments extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        if ($this->tableExists('invoices')) {
            $invoice_tbl = $this->useTableForAlter('invoices');
            $invoice_tbl->dropColumn('allow_payments');
        }

        if ($this->tableExists('recurring_profiles')) {
            $recurring_tbl = $this->useTableForAlter('recurring_profiles');
            $recurring_tbl->dropColumn('allow_payments');
        }
        $this->doneUsingTables();
    }
}
