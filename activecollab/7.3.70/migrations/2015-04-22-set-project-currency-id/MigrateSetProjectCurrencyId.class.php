<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Set project currency id.
 *
 * @package ActiveCollab.migrations
 */
class MigrateSetProjectCurrencyId extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$projects_table, $currencies_table, $companies_table] = $this->useTables('projects', 'currencies', 'companies');

        /* Get default currency id. */
        $default_currency_id = $this->executeFirstCell("SELECT id FROM $currencies_table WHERE is_default = ?", true);

        /* Update project who have invalid currency id. */
        if ($rows = $this->execute("SELECT p.id, c.currency_id FROM $projects_table as p LEFT JOIN $companies_table as c ON c.id = p.company_id WHERE p.currency_id = ?", 0)) {
            foreach ($rows as $row) {
                $currency_id = $row['currency_id'] ? $row['currency_id'] : $default_currency_id;

                $this->execute("UPDATE $projects_table SET currency_id = ? WHERE id = ? AND currency_id = ?", $currency_id, $row['id'], 0);
            }
        }

        $this->doneUsingTables();
    }
}
