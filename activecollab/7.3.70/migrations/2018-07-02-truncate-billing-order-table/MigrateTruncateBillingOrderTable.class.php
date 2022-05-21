<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateTruncateBillingOrderTable extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('billing_orders')) {
            DB::execute('TRUNCATE TABLE billing_orders'); // clear records with old 'type' values
        }
    }
}
