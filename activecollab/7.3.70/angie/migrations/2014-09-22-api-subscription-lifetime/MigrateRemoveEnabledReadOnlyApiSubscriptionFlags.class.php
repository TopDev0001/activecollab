<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove is_enabled and is_read_only flags from API subscriptions model.
 *
 * @package angie.migrations
 */
class MigrateRemoveEnabledReadOnlyApiSubscriptionFlags extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $api_subscriptions = $this->useTableForAlter('api_subscriptions');

        $this->execute('DELETE FROM ' . $api_subscriptions->getName() . ' WHERE is_enabled = ?', false);

        $api_subscriptions->dropColumn('is_enabled');
        $api_subscriptions->dropColumn('is_read_only');

        $this->doneUsingTables();
    }
}
