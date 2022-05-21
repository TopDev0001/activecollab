<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateFixProjectsCurrencies extends AngieModelMigration
{
    public function up()
    {
        [$projects, $currencies] = $this->useTables('projects', 'currencies');

        $default_currency_id = (int) $this->executeFirstCell(
            "SELECT id FROM {$currencies} WHERE is_default = ?",
            true
        );

        $currency_ids = $this->executeFirstColumn("SELECT id FROM {$currencies}");

        if ($default_currency_id && $currency_ids) {
            $this->execute(
                "UPDATE {$projects} SET currency_id = ? WHERE currency_id NOT IN (?)",
                $default_currency_id,
                $currency_ids
            );
        }

        $this->doneUsingTables();
    }
}
