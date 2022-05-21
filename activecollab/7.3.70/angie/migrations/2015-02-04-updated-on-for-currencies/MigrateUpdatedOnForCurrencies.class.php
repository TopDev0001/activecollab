<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Added updated_on property to currencies table.
 *
 * @package angie.migrations
 */
class MigrateUpdatedOnForCurrencies extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $currencies = $this->useTableForAlter('currencies');
        $currencies->addColumn(new DBUpdatedOnColumn(), 'is_default');

        $this->execute('UPDATE ' . $currencies->getName() . ' SET updated_on = UTC_TIMESTAMP()');

        $this->doneUsingTables();
    }
}
