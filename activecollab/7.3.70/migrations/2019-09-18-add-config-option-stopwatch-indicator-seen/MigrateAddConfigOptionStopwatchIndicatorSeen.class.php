<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddConfigOptionStopwatchIndicatorSeen extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('stopwatch_indicator_seen', false);
    }
}
