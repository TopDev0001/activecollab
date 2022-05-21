<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Retire incoming mailboxes.
 *
 * @package angie.migrations
 */
class MigrateRetireIncomingMailboxes extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->dropTable('incoming_mail_filters');
        $this->dropTable('incoming_mails');
        $this->dropTable('incoming_mail_attachments');

        $mailboxes = $this->useTableForAlter('incoming_mailboxes');

        if ($mailboxes_count = $this->executeFirstCell('SELECT COUNT(id) AS "row_count" FROM ' . $mailboxes->getName() . ' WHERE is_enabled = ?', true)) {
            $mailboxes->dropColumn('type');
            $mailboxes->dropColumn('last_status');
            $mailboxes->dropColumn('is_enabled');
            $mailboxes->dropColumn('failure_attempts');
        }

        $this->doneUsingTables();

        if ($mailboxes_count) {
            $this->renameTable('incoming_mailboxes', 'backup_incoming_mailboxes');
        } else {
            $this->dropTable('incoming_mailboxes');
        }
    }
}
