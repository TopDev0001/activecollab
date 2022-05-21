<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Backup document.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateBackUpDocuments extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$documents, $categories, $projects, $project_users, $files, $text_documents, $subscriptions, $favorites, $attachments] = $this->useTables('documents', 'categories', 'projects', 'project_users', 'files', 'text_documents', 'subscriptions', 'favorites', 'attachments');

        if ($this->isModuleInstalled('documents')) {
            [$owner_id, $owner_name, $owner_email] = $this->getFirstUsableOwner();

            $escaped_owner_id = DB::escape($owner_id);

            if ($this->execute("INSERT INTO $projects (slug, mail_to_project_code, company_id, name, leader_id, leader_name, leader_email, created_on, created_by_id, created_by_name, created_by_email, updated_on) VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), ?, ?, ?, UTC_TIMESTAMP())", $this->getSlug($projects), $this->getMailToProjectCode($projects), $this->getOwnerCompanyId(), 'Documents Backup', $owner_id, $owner_name, $owner_email, $owner_id, $owner_name, $owner_email)) {
                $project_id = $this->lastInsertId();

                $escaped_project_id = DB::escape($project_id);

                $this->execute("INSERT INTO $project_users (project_id, user_id) VALUES ($escaped_project_id, $escaped_owner_id)");

                // ---------------------------------------------------
                //  Files are easy - no attachments
                // ---------------------------------------------------

                if ($rows = $this->execute("SELECT name, mime_type, size, location, md5, created_on, created_by_id, created_by_name, created_by_email, visibility FROM $documents WHERE type = 'file' AND state > ? ORDER BY id", 0)) {
                    $batch = new DBBatchInsert($files, ['type', 'project_id', 'name', 'mime_type', 'size', 'location', 'md5', 'created_on', 'created_by_id', 'created_by_name', 'created_by_email', 'updated_on', 'is_hidden_from_clients']);

                    foreach ($rows as $row) {
                        if (empty($row['size'])) {
                            $row['size'] = 0;
                        }

                        $batch->insert('File', $project_id, $row['name'], $row['mime_type'], $row['size'], $row['location'], $row['md5'], $row['created_on'], $row['created_by_id'], $row['created_by_name'], $row['created_by_email'], $row['created_on'], empty($row['visibility']));
                    }

                    $batch->done();
                }

                // ---------------------------------------------------
                //  Text documents have file attachments
                // ---------------------------------------------------

                if ($rows = $this->execute("SELECT id, name, body, created_on, created_by_id, created_by_name, created_by_email, visibility FROM $documents WHERE type = 'text' AND state > ? ORDER BY id", 0)) {
                    foreach ($rows as $row) {
                        $this->execute("INSERT INTO $text_documents (project_id, name, body, created_on, created_by_id, created_by_name, created_by_email, updated_on, is_hidden_from_clients) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", $project_id, $row['name'], $row['body'], $row['created_on'], $row['created_by_id'], $row['created_by_name'], $row['created_by_email'], $row['created_on'], empty($row['visibility']));

                        $text_document_id = $this->lastInsertId();

                        $this->execute("UPDATE $favorites SET parent_type = 'TextDocument', parent_id = '$text_document_id' WHERE parent_type = 'Document' AND parent_id = ?", $row['id']);
                        $this->execute("UPDATE $attachments SET parent_type = 'TextDocument', parent_id = '$text_document_id', project_id = '$project_id' WHERE parent_type = 'Document' AND parent_id = ?", $row['id']);
                    }
                }
            }
        }

        $this->execute("DELETE FROM $categories WHERE type = 'DocumentsCategory'");
        $this->execute("DELETE FROM $favorites WHERE parent_type = 'Document'");
        $this->execute("DELETE FROM $subscriptions WHERE parent_type = 'Document'");

        $this->removeModule('documents');

        $this->doneUsingTables();

        if ($this->tableExists('documents')) {
            $this->dropTable('documents');
        }
    }

    /**
     * @param  string $projects_table
     * @return string
     */
    private function getSlug($projects_table)
    {
        $slug = 'documents-backup';
        $counter = 1;

        while ($this->executeFirstCell("SELECT COUNT(id) AS 'row_count' FROM $projects_table WHERE slug = ?", $slug)) {
            $slug = 'documents-backup-' . $counter++;
        }

        return $slug;
    }

    /**
     * @param  string $projects_table
     * @return string
     */
    private function getMailToProjectCode($projects_table)
    {
        do {
            $mail_to_project_code = make_string(7);
        } while ($this->executeFirstCell("SELECT COUNT(id) AS 'row_count' FROM $projects_table WHERE mail_to_project_code = ?", $mail_to_project_code));

        return $mail_to_project_code;
    }

    /**
     * @return int
     */
    private function getOwnerCompanyId()
    {
        return $this->executeFirstCell('SELECT id FROM ' . $this->useTables('companies')[0] . ' WHERE is_owner = ? ORDER BY id DESC LIMIT 0, 1', true);
    }
}
