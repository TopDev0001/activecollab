<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\Features\ChatFeatureInterface;

class MigrateTurnOnChatFeatureToAllAccounts extends AngieModelMigration
{
    public function up()
    {
        if (AngieApplication::isOnDemand()) {
            $this->addConfigOption(ChatFeatureInterface::NAME . '_enabled', true);
            $this->addConfigOption(ChatFeatureInterface::NAME . '_enabled_lock', true);
        }
    }
}
