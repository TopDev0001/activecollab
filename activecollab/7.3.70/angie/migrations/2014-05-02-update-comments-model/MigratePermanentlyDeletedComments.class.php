<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate permanently deleted comments.
 *
 * @package angie.migrations
 */
class MigratePermanentlyDeletedComments extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $comments = $this->useTables('comments')[0];

        defined('STATE_DELETED') or define('STATE_DELETED', 0);

        if ($rows = $this->execute("SELECT id, type FROM $comments WHERE state = ?", STATE_DELETED)) {
            $comment_ids = [];
            $comment_type_ids_map = [];

            foreach ($rows as $row) {
                $comment_ids[] = $row['id'];

                if (empty($comment_type_ids_map[$row['type']])) {
                    $comment_type_ids_map[$row['type']] = [];
                }

                $comment_type_ids_map[$row['type']][] = $row['id'];
            }

            $parent_comment_conditions = [];

            foreach ($comment_type_ids_map as $type => $ids) {
                $parent_comment_conditions[] = DB::prepare('(parent_type = ? AND parent_id IN (?))', $type, $ids);
            }

            $parent_comment_conditions = '(' . implode(' OR ', $parent_comment_conditions) . ')';

            $this->cleanUpAttachments($parent_comment_conditions);

            $this->execute("DELETE FROM $comments WHERE id IN (?)", $comment_ids);
        }

        $this->doneUsingTables();
    }

    /**
     * Clean up attachments.
     *
     * @param string $parent_comment_conditions
     */
    private function cleanUpAttachments($parent_comment_conditions)
    {
        $attachments = $this->useTables('attachments')[0];

        if ($attachments_to_delete = $this->execute("SELECT id, location FROM $attachments WHERE $parent_comment_conditions")) {
            $attachment_ids = [];

            foreach ($attachments_to_delete as $attachment_to_delete) {
                $attachment_ids[] = $attachment_to_delete['id'];

                if ($attachment_to_delete['location'] && is_file(UPLOAD_PATH . '/' . $attachment_to_delete['location'])) {
                    @unlink(UPLOAD_PATH . '/' . $attachment_to_delete['location']);
                }
            }

            $this->execute("DELETE FROM $attachments WHERE id IN (?)", $attachment_ids);
        }
    }
}
