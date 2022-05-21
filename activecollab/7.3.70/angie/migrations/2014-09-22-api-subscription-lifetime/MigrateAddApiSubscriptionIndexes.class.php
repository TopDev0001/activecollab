<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add indexes to API subscription table.
 *
 * @package angie.migrations
 */
class MigrateAddApiSubscriptionIndexes extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $api_subscriptions = $this->useTableForAlter('api_subscriptions');

        if (!$api_subscriptions->indexExists('token')) {
            $api_subscriptions->addIndex(DBIndex::create('token', DBIndex::UNIQUE, 'token'));
        }

        if (!$api_subscriptions->indexExists('user_id')) {
            $api_subscriptions->addIndex(DBIndex::create('user_id'));
        }

        $this->doneUsingTables();
    }
}
