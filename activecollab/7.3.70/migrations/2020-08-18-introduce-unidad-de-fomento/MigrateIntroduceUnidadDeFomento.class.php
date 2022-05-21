<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateIntroduceUnidadDeFomento extends AngieModelMigration
{
    public function up()
    {
        if (!$this->hasUnidadDeFomento()) {
            $this->execute(
                'INSERT INTO `currencies`
                        (`name`, `code`, `symbol`, `symbol_native`, `decimal_spaces`, `decimal_rounding`, `updated_on`)
                        VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP)',
                    'Unidad de Fomento',
                    'CLF',
                    'UF',
                    'UF',
                    3,
                    0
            );
        }
    }

    private function hasUnidadDeFomento(): bool
    {
        return (bool) $this->executeFirstCell(
            'SELECT COUNT(`id`) AS "row_count" FROM `currencies` WHERE `code` = ?',
            'CLF'
        );
    }
}
