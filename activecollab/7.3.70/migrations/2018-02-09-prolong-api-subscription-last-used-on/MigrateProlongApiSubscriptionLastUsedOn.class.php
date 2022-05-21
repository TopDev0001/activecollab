<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateProlongApiSubscriptionLastUsedOn extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('api_subscriptions')) {
            $this->execute('UPDATE api_subscriptions SET last_used_on = DATE_ADD(last_used_on, INTERVAL 3 MONTH)');
        }
    }
}
