<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop mailing activity logs table.
 *
 * @package angie.migrations
 */
class MigrateDropMailingActivityLogs extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->dropTable('mailing_activity_logs');
    }
}
