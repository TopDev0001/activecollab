<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove temporary attachments.
 *
 * @package angie.migrations
 */
class MigrateRemoveTemporaryAttachments extends AngieModelMigration
{
    /**
     * Construct the migration.
     */
    public function __construct()
    {
        $this->executeAfter('MigrateRemoveStateFromAttachments');
    }

    /**
     * Migrate ups.
     */
    public function up()
    {
        $attachments = $this->useTables('attachments')[0];

        if ($rows = $this->execute("SELECT id, location FROM $attachments WHERE parent_type = '' OR parent_type IS NULL or parent_id = '0' OR parent_id IS NULL")) {
            $attachment_ids = [];

            foreach ($rows as $row) {
                $attachment_ids[] = $row['id'];

                $path = UPLOAD_PATH . '/' . $row['location'];

                if (is_file($path)) {
                    @unlink($path);
                }
            }

            $this->execute("DELETE FROM $attachments WHERE id IN (?)", $attachment_ids);
        }

        $this->doneUsingTables();
    }
}
