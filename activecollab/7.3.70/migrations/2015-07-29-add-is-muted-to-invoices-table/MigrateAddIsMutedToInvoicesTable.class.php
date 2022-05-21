<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddIsMutedToInvoicesTable extends AngieModelMigration
{
    public function up()
    {
        $invoices = $this->useTableForAlter('invoices');

        if (!$invoices->getColumn('is_muted')) {
            $invoices->addColumn(
                new DBBoolColumn('is_muted'),
                'is_canceled'
            );
        }

        $this->doneUsingTables();
    }
}
