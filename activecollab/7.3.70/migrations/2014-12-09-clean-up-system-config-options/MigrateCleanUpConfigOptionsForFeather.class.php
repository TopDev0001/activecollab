<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Clean up configuration options.
 *
 * @package ActiveCollab.migrations
 */
class MigrateCleanUpConfigOptionsForFeather extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('clients_can_delegate_to_employees');
        $this->removeConfigOption('identity_client_welcome_message');
        $this->removeConfigOption('identity_logo_on_white');
        $this->removeConfigOption('new_modules_available');
        $this->removeConfigOption('mail_to_project');
        $this->removeConfigOption('mail_to_project_default_action');
        $this->removeConfigOption('my_tasks_labels_filter');
        $this->removeConfigOption('my_tasks_labels_filter_data');
        $this->removeConfigOption('default_project_object_visibility');
    }
}
