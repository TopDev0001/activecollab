<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateNotificationsForFeatherTake2 extends AngieModelMigration
{
    public function up()
    {
        $this->removeConfigOption('notifications_fetched_on');

        $this->useTableForAlter('notification_recipients')->addColumn(
            new DBDateTimeColumn('read_on'),
            'recipient_email'
        );
        $this->doneUsingTables();
    }
}
