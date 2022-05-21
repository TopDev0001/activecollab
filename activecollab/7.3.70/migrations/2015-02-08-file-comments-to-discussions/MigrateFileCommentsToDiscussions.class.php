<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate file comments to discussion.
 *
 * @package ActiveCollab.migrations
 */
class MigrateFileCommentsToDiscussions extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$files, $discussions, $comments, $attachments] = $this->useTables('files', 'discussions', 'comments', 'attachments');

        if ($commented_files = $this->execute("SELECT DISTINCT f.id, f.project_id, f.name, f.created_on, f.created_by_id, f.created_by_name, f.created_by_email, f.updated_on FROM $files AS f LEFT JOIN $comments AS c ON c.parent_type = 'File' AND c.parent_id = f.id WHERE c.parent_id IS NOT NULL")) {
            foreach ($commented_files as $commented_file) {
                $body = 'Comments on <a object-id="' . $commented_file['id'] . '" object-class="File">' . clean($commented_file['name']) . ' file</a>.';

                $this->execute("INSERT INTO $discussions (project_id, name, body, created_on, created_by_id, created_by_name, created_by_email, updated_on) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", $commented_file['project_id'], $commented_file['name'], $body, $commented_file['created_on'], $commented_file['created_by_id'], $commented_file['created_by_name'], $commented_file['created_by_email'], $commented_file['updated_on']);
                $this->execute("UPDATE $comments SET parent_type = 'Discussion', parent_id = ? WHERE parent_type = 'File' AND parent_id = ?", $this->lastInsertId(), $commented_file['id']);
            }
        }

        if ($orphan_comment_ids = $this->execute("SELECT id FROM $comments WHERE parent_type = 'File'")) {
            if ($attachments_to_delete = $this->execute("SELECT id, location FROM $attachments WHERE parent_type = 'Comment' AND parent_id IN (?)", $orphan_comment_ids)) {
                foreach ($attachments_to_delete as $attachment_to_delete) {
                    AngieApplication::removeStoredFile($attachment_to_delete['location']);
                }

                $this->execute("DELETE FROM $attachments WHERE parent_type = 'Comment' AND parent_id IN (?)", $orphan_comment_ids);
            }

            $this->execute("DELETE FROM $comments WHERE id IN (?)", $orphan_comment_ids);
        }

        $this->doneUsingTables();
    }
}
