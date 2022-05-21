<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop type field from comments model.
 *
 * @package angie.migrations
 */
class MigrateDropTypeFromCommentsModel extends AngieModelMigration
{
    /**
     * Migrate permanently deleted comments.
     */
    public function __construct()
    {
        $this->executeAfter('MigratePermanentlyDeletedComments');
    }

    /**
     * Drop type field from comments model.
     */
    public function up()
    {
        $comments = $this->useTableForAlter('comments');

        $this->cleanUpAttachments($comments->getName());

        $comments->dropColumn('type');

        $this->doneUsingTables();
    }

    /**
     * Update attachments.
     *
     * @param string $comments_table
     */
    private function cleanUpAttachments($comments_table)
    {
        $attachments = $this->useTables('attachments')[0];

        // ---------------------------------------------------
        //  Clean up orphan attachments now that we no longer
        //  have comment type dependency
        // ---------------------------------------------------

        if ($attachments_to_delete = $this->executeFirstColumn("SELECT id, location FROM $attachments WHERE parent_type LIKE '%Comment' AND parent_id NOT IN (SELECT id FROM $comments_table)")) {
            $ids_to_delete = [];

            foreach ($attachments_to_delete as $attachment_to_delete) {
                $ids_to_delete[] = $attachment_to_delete['id'];

                if ($attachment_to_delete['location'] && is_file(UPLOAD_PATH . '/' . $attachment_to_delete['location'])) {
                    @unlink(UPLOAD_PATH . '/' . $attachment_to_delete['location']);
                }

                // Delete 100 attachments per DELETE command, so we don't end up with a single query that has 100.000 ID-s to escape
                if (count($ids_to_delete) == 100) {
                    $this->execute("DELETE FROM $attachments WHERE id IN (?)", $ids_to_delete);
                    $ids_to_delete = [];
                }
            }

            if (count($ids_to_delete) > 0) {
                $this->execute("DELETE FROM $attachments WHERE id IN (?)", $ids_to_delete);
            }
        }

        // ---------------------------------------------------
        //  Update parent type
        // ---------------------------------------------------

        $this->execute("UPDATE $attachments SET parent_type = 'Comment' WHERE parent_type LIKE '%Comment'");
    }
}
