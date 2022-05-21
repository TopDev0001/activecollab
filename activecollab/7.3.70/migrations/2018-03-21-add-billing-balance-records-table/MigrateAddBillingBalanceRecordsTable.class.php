<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddBillingBalanceRecordsTable extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('billing_balance_records')) {
            $this->createTable(
                DB::createTable('billing_balance_records')
                    ->addColumns(
                        [
                            new DBIdColumn(),
                            new DBMoneyColumn('amount', 0),
                            new DBMoneyColumn('amount_available', 0),
                            new DBStringColumn('reason'),
                            new DBStringColumn('reason_details'),
                            new DBCreatedOnColumn(),
                            new DBUpdatedOnColumn(),
                        ]
                    )
                    ->addIndices(
                        [
                            DBIndex::create('created_on'),
                        ]
                    )
            );
        }
    }
}
