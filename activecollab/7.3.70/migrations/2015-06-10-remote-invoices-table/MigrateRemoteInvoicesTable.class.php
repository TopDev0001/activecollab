<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remote invoices.
 *
 * @package ActiveCollab.migrations
 */
class MigrateRemoteInvoicesTable extends AngieModelMigration
{
    /**
     *  Remote invoices.
     */
    public function up()
    {
        $this->createTable('remote_invoices', [
            new DBIdColumn(),
            DBStringColumn::create('invoice_number', 45),
            DBStringColumn::create('client', 75),
            DBIntegerColumn::create('remote_id'),
            new DBMoneyColumn('amount', 0),
            new DBMoneyColumn('balance', 0),
            new DBUpdatedOnByColumn(),
        ]);
    }
}
