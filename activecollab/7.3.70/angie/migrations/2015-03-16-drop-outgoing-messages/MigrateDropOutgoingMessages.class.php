<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop outgoing messages queue.
 *
 * @package angie.migrations
 */
class MigrateDropOutgoingMessages extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->dropTable('outgoing_messages');

        $attachments = $this->useTables('attachments')[0];

        if ($attachments_to_delete = $this->executeFirstColumn("SELECT id, location FROM $attachments WHERE parent_type = 'OutgoingMessage'")) {
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
    }
}
