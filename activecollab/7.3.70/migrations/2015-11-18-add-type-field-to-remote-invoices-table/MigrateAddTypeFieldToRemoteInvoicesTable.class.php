<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddTypeFieldToRemoteInvoicesTable extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('remote_invoices')) {
            $this->createTable(
                'remote_invoices',
                [
                    new DBIdColumn(),
                    DBStringColumn::create('invoice_number', 45),
                    DBStringColumn::create('client', 75),
                    DBIntegerColumn::create('remote_id'),
                    new DBMoneyColumn('amount', 0),
                    new DBMoneyColumn('balance', 0),
                    new DBUpdatedOnByColumn(),
                ]
            );
        }

        $remote_invoices = $this->useTableForAlter('remote_invoices');

        if (!$remote_invoices->getColumn('type')) {
            $remote_invoices->addColumn(
                new DBTypeColumn('RemoteInvoice'),
                'id'
            );
        }

        $this->doneUsingTables();
    }
}
