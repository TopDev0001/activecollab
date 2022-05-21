<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddChatShortcutsEnabledConfigOption extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('chat_shortcuts_enabled', true);
    }
}
