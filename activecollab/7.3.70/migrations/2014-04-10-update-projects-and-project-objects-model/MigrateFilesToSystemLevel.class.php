<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate files to system level.
 *
 * @package ActiveCollab.modules.system
 * @subpackage migrations
 */
class MigrateFilesToSystemLevel extends AngieModelMigration
{
    /**
     * Construct migration.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateTasksToNewStorage');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        $files = $this->useTableForAlter('files');

        // ---------------------------------------------------
        //  Cleanup
        // ---------------------------------------------------

        $this->cleanUpNonProjectAndTempFiles($files->getName());
        $this->dropFileVersions();

        $files->dropColumn('category_id');
        $files->dropColumn('is_temporal');

        if ($files->getColumn('kind') instanceof DBColumn) {
            $files->dropColumn('kind');
        }

        if ($files->indexExists('size')) {
            $files->dropIndex('size');
        }

        // ---------------------------------------------------
        //  parent_type parent_id combo to project_id
        // ---------------------------------------------------

        if ($files->indexExists('parent')) {
            $files->dropIndex('parent');
        }

        $files->dropColumn('parent_type');
        $files->alterColumn('parent_id', DBIntegerColumn::create('project_id', DBColumn::NORMAL, 0)->setUnsigned(true));
        $files->addIndex(DBIndex::create('project_id'));

        // ---------------------------------------------------
        //  Drop versioning
        // ---------------------------------------------------

        $files->dropColumn('version');
        $files->dropColumn('last_version_on');
        $files->dropColumn('last_version_by_id');
        $files->dropColumn('last_version_by_name');
        $files->dropColumn('last_version_by_email');

        // ---------------------------------------------------
        //  Is hidden from clients
        // ---------------------------------------------------

        $files->addColumn(
            new DBBoolColumn('is_hidden_from_clients'),
            'md5'
        );

        defined('VISIBILITY_PRIVATE') or define('VISIBILITY_PRIVATE', 0);

        $this->execute('UPDATE ' . $files->getName() . ' SET is_hidden_from_clients = ? WHERE visibility = ?', true, VISIBILITY_PRIVATE);

        $files->dropColumn('visibility');
        $files->dropColumn('original_visibility');

        // ---------------------------------------------------
        //  State
        // ---------------------------------------------------

        $files->addColumn(
            new DBBoolColumn('is_trashed'),
            'is_hidden_from_clients'
        );
        $files->addColumn(
            new DBBoolColumn('original_is_trashed'),
            'is_trashed'
        );
        $files->addColumn(
            new DBDateTimeColumn('trashed_on'),
            'is_trashed'
        );
        $files->addColumn(
            DBFkColumn::create('trashed_by_id'),
            'trashed_on'
        );
        $files->addIndex(DBIndex::create('trashed_by_id'));

        defined('STATE_TRASHED') or define('STATE_TRASHED', 1);

        $this->execute('UPDATE ' . $files->getName() . ' SET is_trashed = ?, original_is_trashed = ?, trashed_on = NOW() WHERE state = ?', true, false, STATE_TRASHED);

        $files->dropColumn('state');
        $files->dropColumn('original_state');

        $this->doneUsingTables();
    }

    /**
     * Clean up non-project and temporal files.
     *
     * @param string $files_table
     */
    public function cleanUpNonProjectAndTempFiles($files_table)
    {
        if ($rows = $this->execute("SELECT id, location FROM $files_table WHERE parent_type != 'Project' OR is_temporal = ?", true)) {
            $file_ids = [];

            foreach ($rows as $row) {
                $file_ids[] = $row['id'];

                if (trim($row['location'])) {
                    $path = UPLOAD_PATH . '/' . $row['location'];

                    if (is_file($path)) {
                        @unlink($path);
                    }
                }
            }

            DB::execute("DELETE FROM $files_table WHERE id IN (?)", $file_ids);
        }
    }

    /**
     * Drop file versions table.
     */
    public function dropFileVersions()
    {
        $file_versions = $this->useTableForAlter('file_versions');

        if ($locations = DB::executeFirstColumn('SELECT DISTINCT location FROM ' . $file_versions->getName())) {
            foreach ($locations as $location) {
                if (trim($location)) {
                    $path = UPLOAD_PATH . '/' . $location;

                    if (is_file($path)) {
                        @unlink($path);
                    }
                }
            }
        }

        $file_versions->delete();
    }
}
