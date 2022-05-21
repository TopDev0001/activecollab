<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove search_adapter configuration option - search adapter is controled via integration now.
 *
 * @package angie.migrations
 */
class MigrateRemoveSearchAdapterConfigOptions extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('search_adapter');
        $this->removeConfigOption('elastic_search_hosts');
        $this->removeConfigOption('elastic_search_number_of_shards');
        $this->removeConfigOption('elastic_search_number_of_replicas');
    }
}
