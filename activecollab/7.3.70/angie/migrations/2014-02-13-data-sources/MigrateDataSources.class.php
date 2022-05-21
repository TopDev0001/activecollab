<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateDataSources extends AngieModelMigration
{
    public function up()
    {
        $data_sources_table = 'data_sources';
        if (!DB::tableExists($data_sources_table)) {
            $this->createTable('data_sources', [
                new DBIdColumn(),
                new DBTypeColumn(),
                new DBNameColumn(50),
                new DBAdditionalPropertiesColumn(),
                new DBCreatedOnByColumn(),
                new DBBoolColumn('is_private'),
            ]);
        }

        $data_sources_mappings_table = 'data_source_mappings';
        if (!DB::tableExists($data_sources_mappings_table)) {
            $this->createTable('data_source_mappings', [
                new DBIdColumn(),
                DBIntegerColumn::create('project_id', 11),
                DBStringColumn::create('source_type', 50, ''),
                DBIntegerColumn::create('source_id', 11),
                DBIntegerColumn::create('parent_id', 11),
                DBStringColumn::create('parent_type', 50, ''),
                DBIntegerColumn::create('external_id', 11),
                DBStringColumn::create('external_type', 50, ''),
                new DBCreatedOnByColumn(),
            ]);
        }
    }
}
