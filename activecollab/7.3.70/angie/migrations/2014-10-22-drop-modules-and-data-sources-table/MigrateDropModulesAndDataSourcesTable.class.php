<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop modules and data sources table.
 *
 * @package angie.migrations
 */
class MigrateDropModulesAndDataSourcesTable extends AngieModelMigration
{
    /**
     * Drop modules table.
     */
    public function up()
    {
        $this->dropTable('modules');
        $this->dropTable('data_source_mappings');
        $this->dropTable('data_sources');
    }
}
