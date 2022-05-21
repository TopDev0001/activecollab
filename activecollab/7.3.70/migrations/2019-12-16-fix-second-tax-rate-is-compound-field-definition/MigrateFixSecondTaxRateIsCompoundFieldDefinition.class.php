<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateFixSecondTaxRateIsCompoundFieldDefinition extends AngieModelMigration
{
    public function up()
    {
        $tables = ['estimates', 'invoices', 'recurring_profiles'];

        foreach ($tables as $table) {
            $this->execute(
                sprintf(
                    'ALTER TABLE `%s` CHANGE COLUMN `second_tax_is_enabled` `second_tax_is_enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT "0";',
                    $table
                )
            );

            $this->execute(
                sprintf(
                    'ALTER TABLE `%s` CHANGE COLUMN `second_tax_is_compound` `second_tax_is_compound` TINYINT(1) UNSIGNED NOT NULL DEFAULT "0";',
                    $table
                )
            );
        }
    }
}
