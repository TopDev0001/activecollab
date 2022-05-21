<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddTimelineEnableConfigOption extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('timeline_enabled', true);
        $this->addConfigOption('timeline_enabled_lock', true);
    }
}
