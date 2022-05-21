<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate recurring_approval_requests.
 *
 * @package ActiveCollab.migrations
 */
class MigrateDropRecurringApprovalRequests extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        if ($this->tableExists('recurring_approval_requests')) {
            $this->dropTable('recurring_approval_requests');
        }
    }
}
