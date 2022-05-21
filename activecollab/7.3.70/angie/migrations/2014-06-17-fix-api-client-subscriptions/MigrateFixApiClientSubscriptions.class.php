<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Fix api client subscriptions.
 *
 * @package angie.migrations
 */
class MigrateFixApiClientSubscriptions extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $subscriptions = $this->useTableForAlter('api_client_subscriptions');
        $subscriptions->dropColumn('type');

        $this->doneUsingTables();

        $this->renameTable('api_client_subscriptions', 'api_subscriptions');
    }
}
