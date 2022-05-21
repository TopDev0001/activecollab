<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddCompanyNameIndexToInvoices extends AngieModelMigration
{
    public function up()
    {
        foreach (['estimates', 'invoices', 'recurring_profiles'] as $table_name) {
            $table = $this->useTableForAlter($table_name);

            if (!$table->getIndex('company_name')) {
                $table->addIndex(DBIndex::create('company_name'));
            }
        }
    }
}
