<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove trigger heartbeat event call from migration class.
 *
 * @package angie.migrations
 */
class MigrateRemoveTriggerHeartbeatEvent extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->execute("DELETE FROM executed_model_migrations WHERE migration = 'MigrateAnnounceTimeline'");
    }
}
