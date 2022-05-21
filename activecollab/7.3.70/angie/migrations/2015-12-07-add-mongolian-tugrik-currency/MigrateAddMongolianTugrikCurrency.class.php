<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add new currency for Mongolia, Mongolian Tugrik.
 *
 * @package angie.migrations
 */
class MigrateAddMongolianTugrikCurrency extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        if ($this->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `currencies` WHERE `code` = ?', 'MNT') < 1) {
            $this->execute("INSERT INTO `currencies` (`name`, `code`, `symbol`, `symbol_native`, `decimal_spaces`, `decimal_rounding`, `is_default`, `updated_on`) VALUES ( 'Mongolian Tugrik', 'MNT', '₮', '₮', '2', '0.000', '0', UTC_TIMESTAMP());");
        }
    }
}
