<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateRoutingCacheForRecurringTasks extends AngieModelMigration
{
    public function up()
    {
        $this->execute('DELETE FROM routing_cache');
        $this->execute('TRUNCATE TABLE routing_cache');
    }
}
