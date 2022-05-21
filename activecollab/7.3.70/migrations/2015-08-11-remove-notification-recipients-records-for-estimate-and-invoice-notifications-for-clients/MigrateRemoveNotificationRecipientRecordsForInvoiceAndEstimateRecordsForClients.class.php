<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate remove notification recipients records for Invoice and Estimate notifications for Client recipients.
 *
 * @package ActiveCollab.migrations
 */
class MigrateRemoveNotificationRecipientRecordsForInvoiceAndEstimateRecordsForClients extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $notification_ids = DB::executeFirstColumn('SELECT id FROM notifications WHERE parent_type IN (?, ?);', 'Estimate', 'Invoice');
        $client_ids = DB::executeFirstColumn('SELECT id FROM users WHERE type = ?;', 'Client');

        if (!empty($notification_ids) && !empty($client_ids)) {
            DB::execute('DELETE FROM notification_recipients WHERE notification_id IN (?) AND recipient_id IN (?)', $notification_ids, $client_ids);
        }
    }
}
