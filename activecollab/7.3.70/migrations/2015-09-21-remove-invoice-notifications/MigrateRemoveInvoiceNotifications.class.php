<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove all invoice and esetimates notifications.
 *
 * @package activeCollab.modules.system
 */
class MigrateRemoveInvoiceNotifications extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        if ($notification_ids = $this->executeFirstColumn('SELECT id FROM notifications WHERE parent_type IN (?)', ['Invoice', 'Estimate'])) {
            // remove all notifications
            $this->execute('DELETE FROM notifications WHERE id IN (?)', $notification_ids);

            // remove all notification recipients related to notifications
            $this->execute('DELETE FROM notification_recipients WHERE notification_id IN (?)', $notification_ids);
        }
    }
}
