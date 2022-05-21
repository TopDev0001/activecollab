<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Prepare files storage.
 *
 * @package angie.migrations
 */
class MigratePrepareFilesStorage extends AngieModelMigration
{
    /**
     * Prepare files storage.
     */
    public function up()
    {
        $attachments_table = $this->useTables('attachments')[0];

        $this->createTable('files', [
            new DBIdColumn(),
            new DBTypeColumn('File'),
            new DBParentColumn(),
            DBIntegerColumn::create('category_id', 11)->setUnsigned(true),
            new DBStateColumn(),
            DBIntegerColumn::create('visibility', 3, 0)->setUnsigned(true)->setSize(DBColumn::TINY),
            DBIntegerColumn::create('original_visibility', 3)->setUnsigned(true)->setSize(DBColumn::TINY),
            new DBNameColumn(150),
            new DBEnumColumn('kind', ['image', 'video', 'audio', 'document', 'archive', 'other']),
            DBStringColumn::create('mime_type', 255, 'application/octet-stream'),
            DBIntegerColumn::create('size', 10, 0)->setUnsigned(true),
            DBStringColumn::create('location', 50),
            DBStringColumn::create('md5', 32),
            new DBBoolColumn('is_temporal', true),
            new DBActionOnByColumn('created', true, true),
            new DBActionOnByColumn('updated', true, true),
            DBIntegerColumn::create('version', DBIntegerColumn::NORMAL, 1)->setUnsigned(true),
            new DBActionOnByColumn('last_version', true, true),
            new DBAdditionalPropertiesColumn(),
        ], [
            DBIndex::create('kind'),
            DBIndex::create('name'),
            DBIndex::create('size'),
        ]);

        if ($this->tableExists('file_versions')) {
            $file_versions = $this->useTableForAlter('file_versions');
            $file_versions->alterColumn('version_num', DBIntegerColumn::create('version', 5, 0)->setUnsigned(true));
        }

        $attachments = $this->useTableForAlter('attachments');
        $attachments->addColumn(
            new DBBoolColumn('is_temporal', true),
            'md5'
        );

        $this->execute("UPDATE $attachments_table SET is_temporal = '0' WHERE parent_type IS NOT NULL AND parent_id > '0'");

        $this->doneUsingTables();
    }
}
