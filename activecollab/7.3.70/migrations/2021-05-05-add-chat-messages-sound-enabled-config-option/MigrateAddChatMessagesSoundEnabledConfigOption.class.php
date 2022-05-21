<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddChatMessagesSoundEnabledConfigOption extends AngieModelMigration
{
    public function up()
    {
        if (!ConfigOptions::exists('chat_messages_sound_enabled')) {
            $this->addConfigOption('chat_messages_sound_enabled', true);
        }
    }
}
