<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddRemoteInvoiceItemsTable extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('remote_invoice_items')) {
            $this->createTable(
                DB::createTable('remote_invoice_items')
                    ->addColumns(
                        [
                            new DBIdColumn(),
                            new DBParentColumn(false),
                            DBStringColumn::create('line_id', 50),
                            new DBMoneyColumn('amount', 0),
                            DBIntegerColumn::create('project_id', 10, 0)->setUnsigned(true),
                            new DBUpdatedOnByColumn(),
                        ]
                    )
            );
        }
    }
}
