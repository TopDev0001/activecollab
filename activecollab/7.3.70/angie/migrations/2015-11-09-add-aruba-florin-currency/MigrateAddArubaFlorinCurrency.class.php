<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add new currency for Aruba, Aruba Florin.
 *
 * @package angie.migrations
 */
class MigrateAddArubaFlorinCurrency extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        if ($this->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `currencies` WHERE `code` = ?', 'AWG') < 1) {
            $this->execute("INSERT INTO `currencies` (`name`, `code`, `symbol`, `symbol_native`, `decimal_spaces`, `decimal_rounding`, `is_default`, `updated_on`) VALUES ( 'Aruba Florin', 'AWG', 'Afl.', 'Afl.', '2', '0.000', '0', UTC_TIMESTAMP());");
        }
    }
}
