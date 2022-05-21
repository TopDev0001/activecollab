<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove tables added by third party modules that are not used by ActiveCollab.
 *
 * @package ActiveCollab.migrations
 */
class MigrateRemoveThirdPartyTables extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        foreach (['job_numbers', 'message_of_the_day', 'rep_finance_summary', 'rep_tickets_snapshot', 'tp_merged_tickets_references', 'tp_support_tickets'] as $table_name) {
            if ($this->tableExists($table_name)) {
                $this->dropTable($table_name);
            }
        }
    }
}
