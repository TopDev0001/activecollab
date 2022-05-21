<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddColumnBasedOnToRemoteInvoicesTable extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('remote_invoices')) {
            $remote_invoices = $this->useTableForAlter('remote_invoices');

            if (!$remote_invoices->getColumn('based_on')) {
                $remote_invoices->addColumn(
                    new DBEnumColumn(
                        'based_on',
                        [
                            'fixed',
                            'time_and_expenses',
                        ],
                        'time_and_expenses'
                    ),
                    'balance'
                );
            }
        }
    }
}
