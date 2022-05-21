<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateFixCurrencyId extends AngieModelMigration
{
    public function up()
    {
        $query = 'SELECT id FROM currencies WHERE id = 1';
        $currency = $this->executeFirstCell($query);

        $query = 'SELECT id FROM currencies WHERE is_default = 1 ORDER BY id LIMIT 1';
        $default_currency = $this->executeFirstCell($query);

        if (!$currency) {
            $update_query = '
                UPDATE projects
                SET currency_id = ?
                WHERE currency_id = 1 AND updated_on BETWEEN DATE("2020-03-25") AND NOW()';
            $this->execute($update_query, $default_currency);
        }
    }
}
