<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate task notification options.
 *
 * @package ActiveCollab.migrations
 */
class MigrateNotificationOptionsForTasks extends AngieModelMigration
{
    /**
     * Migrate ups.
     */
    public function up()
    {
        $this->removeConfigOption('tasks_auto_reopen');
        $this->removeConfigOption('tasks_auto_reopen_clients_only');
        $this->removeConfigOption('tasks_public_submit_enabled');
        $this->removeConfigOption('tasks_use_captcha');
        $this->removeConfigOption('first_milestone_starts_on');

        $this->addConfigOption('notification_new_task', true);
        $this->addConfigOption('notification_task_name_body_update', false);
    }
}
