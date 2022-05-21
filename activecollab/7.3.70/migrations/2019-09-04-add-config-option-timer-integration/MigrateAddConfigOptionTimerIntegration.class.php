<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddConfigOptionTimerIntegration extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('minimal_time_entry', 15);
        $this->addConfigOption('rounding_interval', 0);
    }
}
