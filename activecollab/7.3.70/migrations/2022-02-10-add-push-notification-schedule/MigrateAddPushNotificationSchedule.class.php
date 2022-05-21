<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddPushNotificationSchedule extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('push_notification_schedule', 'always');
        $this->addConfigOption('push_notification_schedule_settings', ['08:00', '17:30']);
    }
}
