<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddChatEmailNotificationConfigOption extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('chat_email_notifications', true);
    }
}
