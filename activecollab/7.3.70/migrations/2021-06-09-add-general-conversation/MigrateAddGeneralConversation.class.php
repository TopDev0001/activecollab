<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Model\Conversation\GeneralConversation;

class MigrateAddGeneralConversation extends AngieModelMigration
{
    public function up()
    {
        $this->execute(
            'INSERT INTO `conversations` (`type`, `created_on`, `updated_on`)
                    VALUES (?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
            GeneralConversation::class
        );
    }
}
