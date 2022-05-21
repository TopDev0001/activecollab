<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Introduce lifetime property to API subscriptions model.
 *
 * @package angie.migrations
 */
class MigrateApiSubscriptionLifetime extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->useTableForAlter('api_subscriptions')->addColumn(DBIntegerColumn::create('lifetime', 10, 0)->setUnsigned(true), 'last_used_on');

        $this->execute('UPDATE ' . $this->useTables('api_subscriptions')[0] . ' SET created_on = UTC_TIMESTAMP() WHERE created_on IS NULL');
        $this->execute('UPDATE ' . $this->useTables('api_subscriptions')[0] . ' SET last_used_on = created_on WHERE last_used_on IS NULL');

        $this->doneUsingTables();
    }
}
