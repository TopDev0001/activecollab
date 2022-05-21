<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Upgrade search index.
 *
 * @package angie.migrations
 */
class MigrateUpgradeSearchIndex extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->renameConfigOption('search_provider', 'search_adapter');
        $this->setConfigOptionValue('search_adapter', 'my_isam');

        $this->addConfigOption('elastic_search_hosts', 'localhost:9200');
        $this->addConfigOption('elastic_search_number_of_shards', 4);
        $this->addConfigOption('elastic_search_number_of_replicas', 1);

        $this->removeConfigOption('help_search_index_version');
        $this->removeConfigOption('search_initialized_on');

        foreach (['documents', 'help', 'names', 'project_objects', 'projects', 'users'] as $search_index) {
            $this->dropTable("search_index_for_{$search_index}");
        }
    }
}
