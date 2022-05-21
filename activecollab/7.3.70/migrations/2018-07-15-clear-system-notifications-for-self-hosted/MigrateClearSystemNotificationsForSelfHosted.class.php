<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateClearSystemNotificationsForSelfHosted extends AngieModelMigration
{
    public function up()
    {
        if (!AngieApplication::isOnDemand()) {
            $this->execute('DELETE FROM `system_notifications`');
        }
    }
}
