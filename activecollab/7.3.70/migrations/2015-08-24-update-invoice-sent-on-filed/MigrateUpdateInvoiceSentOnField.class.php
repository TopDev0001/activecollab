<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Update invoice sent on field.
 *
 * @package ActiveCollab.migrations
 */
class MigrateUpdateInvoiceSentOnField extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$invoices_table] = $this->useTables('invoices');

        $this->execute("UPDATE $invoices_table SET sent_on = created_on WHERE sent_on IS NULL AND recipients IS NOT NULL");

        $this->doneUsingTables();
    }
}
