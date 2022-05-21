<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Model\Conversation\GroupConversation;

class MigrateSetGroupConversationNameToNull extends AngieModelMigration
{
    public function up()
    {
        if ($this->tableExists('conversations')) {
            $this->execute(
                'UPDATE `conversations` SET `name` = NULL WHERE type = ?',
                GroupConversation::class
            );
        }
    }
}
