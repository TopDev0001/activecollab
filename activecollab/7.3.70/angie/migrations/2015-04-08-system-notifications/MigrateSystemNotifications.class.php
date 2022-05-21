<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateSystemNotifications extends AngieModelMigration
{
    public function up()
    {
        $this->createTable(
            'system_notifications',
            [
                new DBIdColumn(),
                new DBTypeColumn(),
                DBFkColumn::create('recipient_id', 0, true),
                new DBDateTimeColumn('created_on'),
                new DBBoolColumn('is_dismissed'),
                new DBAdditionalPropertiesColumn(),
            ]
        );
    }
}
