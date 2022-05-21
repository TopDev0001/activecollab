<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateCurrencyDecimalPlaces extends AngieModelMigration
{
    public function up()
    {
        $query = 'UPDATE currencies SET decimal_spaces = 2, updated_on = UTC_TIMESTAMP() WHERE code = ?';
        $this->execute($query, 'RSD');
        Currencies::clearCache();
    }
}
