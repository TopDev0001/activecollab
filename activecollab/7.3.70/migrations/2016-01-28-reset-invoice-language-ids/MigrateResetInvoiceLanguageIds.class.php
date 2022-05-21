<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateResetInvoiceLanguageIds extends AngieModelMigration
{
    public function up()
    {
        if (empty($this->executeFirstCell('SELECT COUNT(id) FROM memories WHERE `key` = ?', 'transfered_languages_to_feather'))) {
            foreach (['invoices', 'estimates', 'recurring_profiles'] as $table_name) {
                $this->execute("UPDATE $table_name SET language_id = ?", 1);
            }

            $this->execute('DELETE FROM memories WHERE `key` = ?', 'transfered_languages_to_feather');
        }
    }
}
