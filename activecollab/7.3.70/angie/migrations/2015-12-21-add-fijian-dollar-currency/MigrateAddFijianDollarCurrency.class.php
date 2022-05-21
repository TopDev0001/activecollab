<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add new currency for Fiji, Fijian Dollar.
 *
 * @package angie.migrations
 */
class MigrateAddFijianDollarCurrency extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        if ($this->executeFirstCell('SELECT COUNT(`id`) AS "row_count" FROM `currencies` WHERE `code` = ?', 'FJD') < 1) {
            $this->execute("INSERT INTO `currencies` (`name`, `code`, `symbol`, `symbol_native`, `decimal_spaces`, `decimal_rounding`, `is_default`, `updated_on`) VALUES ( 'Fijian Dollar', 'FJD', 'FJ$', 'FJ$', '2', '0.000', '0', UTC_TIMESTAMP());");
        }
    }
}
