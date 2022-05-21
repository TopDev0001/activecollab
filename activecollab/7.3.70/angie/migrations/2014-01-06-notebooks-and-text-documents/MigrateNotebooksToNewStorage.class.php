<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate notebooks to text documents.
 *
 * @package angie.migrations
 */
class MigrateNotebooksToNewStorage extends AngieModelMigration
{
    /**
     * Construct the migration.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateTextDocumentsToNewStorage');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        if ($this->isModuleInstalled('notebooks')) {
            [$project_objects, $versions, $attachments, $subscriptions, $comments, $reminders, $favorites] = $this->useTables('project_objects', 'text_document_versions', 'attachments', 'subscriptions', 'comments', 'reminders', 'favorites');

            if ($rows = $this->execute("SELECT id, project_id, name, body, state, original_state, visibility, original_visibility, created_on, created_by_id, created_by_name, created_by_email, updated_on, updated_by_id, updated_by_name, updated_by_email, position FROM $project_objects WHERE type = ?", 'Notebook')) {
                $rows->setCasting([
                    'id' => DBResult::CAST_INT,
                    'project_id' => DBResult::CAST_INT,
                    'visibility' => DBResult::CAST_INT,
                ]);

                $project_positions = [];

                foreach ($rows as $row) {
                    $project_id = $row['project_id'];

                    if (isset($project_positions[$project_id])) {
                        ++$project_positions[$project_id];
                    } else {
                        $project_positions[$project_id] = 1;
                    }

                    $body = trim($row['body']);
                    if (empty($body)) {
                        $body = '<p><i>Description not provided</i></p>';
                    }

                    // Fix confidential visibility
                    foreach (['state', 'original_state', 'visibility', 'original_visibility'] as $k) {
                        if ($row[$k] === null) {
                            continue;
                        }

                        if ($row[$k] < 0) {
                            $row[$k] = 0;
                        }
                    }

                    $this->execute("INSERT INTO text_documents (type, parent_type, parent_id, legacy_parent_type, legacy_parent_id, name, body, state, original_state, visibility, original_visibility, created_on, created_by_id, created_by_name, created_by_email, updated_on, updated_by_id, updated_by_name, updated_by_email, version, position) VALUES ('ProjectTextDocument', 'Project', ?, 'Notebook', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '1', ?)", $project_id, $row['id'], $row['name'], $body, $row['state'], $row['original_state'], $row['visibility'], $row['original_visibility'], $row['created_on'], $row['created_by_id'], $row['created_by_name'], $row['created_by_email'], $row['updated_on'], $row['updated_by_id'], $row['updated_by_name'], $row['updated_by_email'], $project_positions[$project_id]);

                    $text_document_id = $this->lastInsertId();

                    $this->execute("UPDATE $attachments SET parent_type = 'ProjectTextDocument', parent_id = ? WHERE parent_type = 'Notebook' AND parent_id = ?", $text_document_id, $row['id']);
                    $this->execute("UPDATE $subscriptions SET parent_type = 'ProjectTextDocument', parent_id = ? WHERE parent_type = 'Notebook' AND parent_id = ?", $text_document_id, $row['id']);
                    $this->execute("UPDATE $comments SET type = 'TextDocumentComment', parent_type = 'ProjectTextDocument', parent_id = ? WHERE parent_type = 'Notebook' AND parent_id = ?", $text_document_id, $row['id']);
                    $this->execute("UPDATE $reminders SET parent_type = 'ProjectTextDocument', parent_id = ? WHERE parent_type = 'Notebook' AND parent_id = ?", $text_document_id, $row['id']);
                    $this->execute("UPDATE $favorites SET parent_type = 'ProjectTextDocument', parent_id = ? WHERE parent_type = 'Notebook' AND parent_id = ?", $text_document_id, $row['id']);

                    $position = 0;

                    $this->migratePages('Notebook', $row['id'], $text_document_id, $row['visibility'], '', $position, [$versions, $attachments, $subscriptions, $comments, $reminders, $favorites]);
                }

                $this->execute("DELETE FROM $project_objects WHERE type = 'Notebook'");
            }

            $this->doneUsingTables();
        }
    }

    /**
     * Migrate pages from notebook pages table to text documents.
     *
     * @param string $parent_type
     * @param int    $parent_id
     * @param int    $text_document_id
     * @param int    $visibility
     * @param string $name_prefix
     * @param int    $position
     * @param array  $update_tables
     */
    private function migratePages($parent_type, $parent_id, $text_document_id, $visibility, $name_prefix, &$position, $update_tables)
    {
        $rows = $this->execute('SELECT * FROM notebook_pages WHERE parent_type = ? AND parent_id = ? ORDER BY position', $parent_type, $parent_id);

        if ($rows) {
            [$versions, $attachments, $subscriptions, $comments, $reminders, $favorites] = $update_tables;

            foreach ($rows as $row) {
                $body = trim($row['body']);
                if (empty($body)) {
                    $body = '<p><i>Description not provided</i></p>';
                }

                $additional_properties = null;

                if ($name_prefix) {
                    $additional_properties = serialize([
                        'legacy_notebook_path' => $name_prefix . $row['name'],
                    ]);
                }

                // Fix confidential visibility
                foreach (['state', 'original_state', 'visibility', 'original_visibility'] as $k) {
                    if (array_key_exists($k, $row) && $row[$k] === null) {
                        continue;
                    }

                    if (empty($row[$k]) || $row[$k] < 0) {
                        $row[$k] = 0;
                    }
                }

                $this->execute("INSERT INTO text_documents (type, parent_type, parent_id, legacy_parent_type, legacy_parent_id, name, body, state, original_state, visibility, original_visibility, created_on, created_by_id, created_by_name, created_by_email, updated_on, updated_by_id, updated_by_name, updated_by_email, last_version_on, last_version_by_id, last_version_by_name, last_version_by_email, version, position, raw_additional_properties) VALUES ('ProjectTextDocument', 'ProjectTextDocument', ?, 'NotebookPage', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $text_document_id, $row['id'], $row['name'], $body, $row['state'], $row['original_state'], $visibility, null, $row['created_on'], $row['created_by_id'], $row['created_by_name'], $row['created_by_email'], $row['updated_on'], $row['updated_by_id'], $row['updated_by_name'], $row['updated_by_email'], $row['last_version_on'], $row['last_version_by_id'], $row['last_version_by_name'], $row['last_version_by_email'], $row['version'], ++$position, $additional_properties);

                $page_text_document_id = $this->lastInsertId();

                $this->execute("UPDATE $attachments SET parent_type = 'ProjectTextDocument', parent_id = ? WHERE parent_type = 'NotebookPage' AND parent_id = ?", $page_text_document_id, $row['id']);
                $this->execute("UPDATE $subscriptions SET parent_type = 'ProjectTextDocument', parent_id = ? WHERE parent_type = 'NotebookPage' AND parent_id = ?", $page_text_document_id, $row['id']);
                $this->execute("UPDATE $comments SET type = 'TextDocumentComment', parent_type = 'TextDocument', parent_id = ? WHERE parent_type = 'NotebookPage' AND parent_id = ?", $page_text_document_id, $row['id']);
                $this->execute("UPDATE $reminders SET parent_type = 'ProjectTextDocument', parent_id = ? WHERE parent_type = 'NotebookPage' AND parent_id = ?", $page_text_document_id, $row['id']);
                $this->execute("UPDATE $favorites SET parent_type = 'ProjectTextDocument', parent_id = ? WHERE parent_type = 'NotebookPage' AND parent_id = ?", $page_text_document_id, $row['id']);

                $this->migratePageVersions($row['id'], $page_text_document_id, $versions);
                $this->migratePages('NotebookPage', $row['id'], $text_document_id, $visibility, ($name_prefix . $row['name'] . ' / '), $position, $update_tables);
            }
        }
    }

    /**
     * Migrate notebook page versions.
     *
     * @param int    $notebook_page_id
     * @param int    $text_document_id
     * @param string $versions_table
     */
    private function migratePageVersions($notebook_page_id, $text_document_id, $versions_table)
    {
        if ($rows = $this->execute('SELECT * FROM notebook_page_versions WHERE notebook_page_id = ?', $notebook_page_id)) {
            $batch = new DBBatchInsert($versions_table, ['text_document_id', 'version_num', 'name', 'body', 'created_on', 'created_by_id', 'created_by_name', 'created_by_email']);

            foreach ($rows as $row) {
                $batch->insert($text_document_id, $row['version'], $row['name'], $row['body'], $row['created_on'], $row['created_by_id'], $row['created_by_name'], $row['created_by_email']);
            }
        }
    }
}
