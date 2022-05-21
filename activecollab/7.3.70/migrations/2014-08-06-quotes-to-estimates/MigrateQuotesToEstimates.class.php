<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Rename quotes to estimates.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage models
 */
class MigrateQuotesToEstimates extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->renameTable('quotes', 'estimates');

        foreach ($this->useTables('comments', 'subscriptions', 'invoice_items') as $table) {
            $this->execute("UPDATE $table SET parent_type = 'Estimate' WHERE parent_type = 'Quote'");
        }

        foreach ($this->useTables('projects', 'invoices') as $table) {
            $this->execute("UPDATE $table SET based_on_type = 'Estimate' WHERE based_on_type = 'Quote'");
        }

        $this->doneUsingTables();
    }
}
