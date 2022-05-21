<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateAddSubscribeOnMentionConfigOption extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('subscribe_on_mention', true);
    }
}
