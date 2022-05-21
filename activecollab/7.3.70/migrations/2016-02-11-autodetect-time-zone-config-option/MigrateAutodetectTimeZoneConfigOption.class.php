<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Add config option for autodetecing timezone.
 *
 * @package activeCollab.modules.system
 */
class MigrateAutodetectTimeZoneConfigOption extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('time_timezone_autodetect', true);
    }
}
