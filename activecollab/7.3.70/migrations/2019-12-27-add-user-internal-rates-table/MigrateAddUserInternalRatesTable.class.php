<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddUserInternalRatesTable extends AngieModelMigration
{
    public function up()
    {
        if (!$this->tableExists('user_internal_rates')) {
            $this->createTable(
                DB::createTable('user_internal_rates')
                    ->addColumns(
                        [
                            new DBIdColumn(),
                            new DBUserColumn('user'),
                            new DBCreatedOnByColumn(),
                            new DBDateColumn('valid_from'),
                            new DBMoneyColumn('rate', 0),
                        ]
                    )
            );
        }
    }
}
