<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate to simple payment class (Payment instead of gateway-specific payments).
 *
 * @package angie.migrations
 */
class MigrateToSinglePaymentClass extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $payments = $this->useTableForAlter('payments');

        $payments->dropColumn('type');
        $payments->alterColumn('currency_id', DBFkColumn::create('currency_id'));

        $this->doneUsingTables();
    }
}
