<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddRequestsCountsToApiSubscriptions extends AngieModelMigration
{
    public function up()
    {
        $this->useTableForAlter('api_subscriptions')->addColumn(DBIntegerColumn::create('requests_count', 10, 1)->setUnsigned(true), 'last_used_on');
    }
}
