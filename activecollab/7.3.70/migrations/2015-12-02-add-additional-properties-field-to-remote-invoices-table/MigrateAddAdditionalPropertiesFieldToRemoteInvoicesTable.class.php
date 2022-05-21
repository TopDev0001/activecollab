<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddAdditionalPropertiesFieldToRemoteInvoicesTable extends AngieModelMigration
{
    public function up()
    {
        $remote_invoices = $this->useTableForAlter('remote_invoices');

        if (!$remote_invoices->getColumn('raw_additional_properties')) {
            $remote_invoices->addColumn(new DBAdditionalPropertiesColumn(), 'balance');
        }

        $this->doneUsingTables();
    }
}
