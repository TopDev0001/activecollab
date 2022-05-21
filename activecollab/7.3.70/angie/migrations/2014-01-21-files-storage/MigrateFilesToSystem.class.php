<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate files to system module.
 *
 * @package angie.migrations
 */
class MigrateFilesToSystem extends AngieModelMigration
{
    /**
     * Execute when storage is ready.
     */
    public function __construct()
    {
        $this->executeAfter('MigratePrepareFilesStorage');
    }

    /**
     * Migrate up.
     */
    public function up()
    {
        [$project_objects, $files, $file_versions, $categories, $comments, $attachments, $subscriptions, $favorites] = $this->useTables('project_objects', 'files', 'file_versions', 'categories', 'comments', 'attachments', 'subscriptions', 'favorites');

        $category_ids = [];

        if ($rows = $this->execute("SELECT id, project_id, category_id, state, original_state, visibility, original_visibility, name, integer_field_1 AS 'version', integer_field_2 AS 'size', varchar_field_1 AS 'mime_type', varchar_field_2 AS 'location', created_on, created_by_id, created_by_name, created_by_email, updated_on, updated_by_id, updated_by_name, updated_by_email, datetime_field_1 AS 'last_version_on', integer_field_3 AS 'last_version_by_id', text_field_1 AS 'last_version_by_name', text_field_2 AS 'last_version_by_email' FROM $project_objects WHERE type = 'File' AND state >= ? ORDER BY created_on", STATE_TRASHED)) {
            $batch = new DBBatchInsert($files, ['id', 'type', 'parent_type', 'parent_id', 'category_id', 'state', 'original_state', 'visibility', 'original_visibility', 'name', 'mime_type', 'size', 'location', 'is_temporal', 'created_on', 'created_by_id', 'created_by_name', 'created_by_email', 'updated_on', 'updated_by_id', 'updated_by_name', 'updated_by_email', 'version', 'last_version_on', 'last_version_by_id', 'last_version_by_name', 'last_version_by_email']);

            $skipped_file_ids = [];

            foreach ($rows as $row) {
                if (empty($row['location']) || empty($row['size'])) {
                    $skipped_file_ids[] = $row['id'];
                    continue;
                }

                $mime_type = $row['mime_type'] ? $row['mime_type'] : 'application/octet-stream';

                $category_id = (int) $row['category_id'];

                if ($category_id && !in_array($category_id, $category_ids)) {
                    $category_ids[] = $category_id;
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

                $batch->insert($row['id'], 'File', 'Project', $row['project_id'], $category_id, $row['state'], $row['original_state'], $row['visibility'], $row['original_visibility'], $row['name'], $mime_type, (int) $row['size'], $row['location'], false, $row['created_on'], $row['created_by_id'], $row['created_by_name'], $row['created_by_email'], $row['updated_on'], $row['updated_by_id'], $row['updated_by_name'], $row['updated_by_email'], $row['version'], $row['last_version_on'], $row['last_version_by_id'], $row['last_version_by_name'], $row['last_version_by_email']);
            }

            $batch->done();

            if (count($skipped_file_ids)) {
                $this->execute("DELETE FROM $file_versions WHERE file_id IN (?)", $skipped_file_ids);

                $comment_ids = $this->executeFirstColumn("SELECT id FROM $comments WHERE parent_type = 'File' AND parent_id IN (?)", $skipped_file_ids);
                if ($comment_ids) {
                    $this->execute("DELETE FROM $attachments WHERE parent_type = 'ProjecObjectComment' AND parent_id IN (?)", $comment_ids);
                    $this->execute("DELETE FROM $comments WHERE id IN (?)", $comment_ids);
                }

                $attachment_rows = $this->execute("SELECT id, location FROM $attachments WHERE parent_type = 'File' AND parent_id IN (?)", $skipped_file_ids);
                if ($attachment_rows) {
                    $attachment_ids = [];

                    foreach ($attachment_rows as $attachment_row) {
                        $attachment_ids[] = (int) $attachment_row['id'];

                        if ($attachment_row['location'] && is_file(UPLOAD_PATH . '/' . $attachment_row['location'])) {
                            @unlink(UPLOAD_PATH . '/' . $attachment_row['location']);
                        }
                    }

                    $this->execute("DELETE $attachments WHERE id IN (?)", $attachment_ids);
                }

                $this->execute("DELETE FROM $subscriptions WHERE parent_type = 'File' AND parent_id IN (?)", $skipped_file_ids);
                $this->execute("DELETE FROM $favorites WHERE parent_type = 'File' AND parent_id IN (?)", $skipped_file_ids);
            }
        }

        $this->execute("UPDATE $comments SET type = 'FileComment' WHERE type = 'AssetComment'");
        $this->execute("DELETE FROM $project_objects WHERE type = 'File'");

        if (empty($category_ids)) {
            DB::execute("DELETE FROM $categories WHERE type = 'AssetCategory'");
        } else {
            DB::execute("DELETE FROM $categories WHERE type = 'AssetCategory' AND id NOT IN (?)", $category_ids);
            DB::execute("UPDATE $categories SET type = 'FileCategory' WHERE type = 'AssetCategory'");
        }

        $this->doneUsingTables();
    }
}
