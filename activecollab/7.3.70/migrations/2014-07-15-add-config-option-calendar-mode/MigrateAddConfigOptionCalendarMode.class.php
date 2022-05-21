<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add config option calendar mode.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateAddConfigOptionCalendarMode extends AngieModelMigration
{
    /**
     * Upgrade the data.
     */
    public function up()
    {
        $this->addConfigOption('calendar_mode', 'monthly');
    }
}
