<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate notification filter configuration options.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateNotificationConfigOptions extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('notification_new_text_document', true);
        $this->addConfigOption('notification_text_document_name_body_update', false);
    }
}
