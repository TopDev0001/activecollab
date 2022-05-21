<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate text documents to the system level.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateTextDocumentsToSystemLevel extends AngieModelMigration
{
    /**
     * Execute after files migration.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateFilesToSystemLevel');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        $text_documents = $this->useTableForAlter('text_documents');

        $text_documents->dropColumn('type');
        $text_documents->dropColumn('version');

        // ---------------------------------------------------
        //  Parent type and parent ID
        // ---------------------------------------------------

        $text_documents->addColumn(DBIntegerColumn::create('project_id', 10, 0)->setUnsigned(true), 'id');
        $text_documents->addColumn(DBIntegerColumn::create('text_document_id', 10, 0)->setUnsigned(true), 'project_id');

        $this->execute('UPDATE ' . $text_documents->getName() . " SET project_id = parent_id WHERE parent_type = 'Project'");
        $this->execute('UPDATE ' . $text_documents->getName() . " SET text_document_id = parent_id WHERE parent_type = 'ProjectTextDocument'");

        if ($rows = $this->execute('SELECT id, project_id FROM ' . $text_documents->getName() . " WHERE parent_type = 'Project'")) {
            $project_text_documents = [];

            foreach ($rows as $row) {
                if (empty($project_text_documents[$row['project_id']])) {
                    $project_text_documents[$row['project_id']] = [];
                }

                $project_text_documents[$row['project_id']][] = $row['id'];
            }

            foreach ($project_text_documents as $project_id => $text_document_ids) {
                $this->execute('UPDATE ' . $text_documents->getName() . " SET project_id = ? WHERE parent_type = 'ProjectTextDocument' AND parent_id IN (?)", $project_id, $text_document_ids);
            }
        }

        $text_document_ids = $this->executeFirstColumn('SELECT id FROM ' . $text_documents->getName() . " WHERE project_id = '0' OR project_id IS NULL");

        if ($text_document_ids && is_foreachable($text_document_ids)) {
            $this->execute('DELETE FROM ' . $text_documents->getName() . ' WHERE id IN (?)', $text_document_ids);
        }

        $text_documents->dropColumn('parent_type');
        $text_documents->dropColumn('parent_id');

        $text_documents->dropColumn('legacy_parent_type');
        $text_documents->dropColumn('legacy_parent_id');

        $text_documents->dropColumn('last_version_on');
        $text_documents->dropColumn('last_version_by_id');
        $text_documents->dropColumn('last_version_by_name');
        $text_documents->dropColumn('last_version_by_email');

        // ---------------------------------------------------
        //  Is hidden from clients
        // ---------------------------------------------------

        $text_documents->addColumn(
            new DBBoolColumn('is_hidden_from_clients'),
            'position'
        );

        defined('VISIBILITY_PRIVATE') or define('VISIBILITY_PRIVATE', 0);

        $this->execute('UPDATE ' . $text_documents->getName() . ' SET is_hidden_from_clients = ? WHERE visibility = ?', true, VISIBILITY_PRIVATE);

        $text_documents->dropColumn('visibility');
        $text_documents->dropColumn('original_visibility');

        // ---------------------------------------------------
        //  State
        // ---------------------------------------------------

        $text_documents->addColumn(
            new DBBoolColumn('is_trashed'),
            'is_hidden_from_clients'
        );
        $text_documents->addColumn(
            new DBBoolColumn('original_is_trashed'),
            'is_trashed'
        );
        $text_documents->addColumn(
            new DBDateTimeColumn('trashed_on'),
            'is_trashed'
        );
        $text_documents->addColumn(
            DBFkColumn::create('trashed_by_id'),
            'trashed_on'
        );
        $text_documents->addIndex(DBIndex::create('trashed_by_id'));

        defined('STATE_TRASHED') or define('STATE_TRASHED', 1);

        $this->execute('UPDATE ' . $text_documents->getName() . ' SET is_trashed = ?, original_is_trashed = ?, trashed_on = NOW() WHERE state = ?', true, false, STATE_TRASHED);

        $text_documents->dropColumn('state');
        $text_documents->dropColumn('original_state');

        // ---------------------------------------------------
        //  Drop versions
        // ---------------------------------------------------

        $this->dropTable('text_document_versions');

        $this->doneUsingTables();
    }
}
