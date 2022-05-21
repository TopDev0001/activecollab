<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddConfigOptionRoundingEnabled extends AngieModelMigration
{
    public function up()
    {
        $rounding_interval = $this->getConfigOptionValue('rounding_interval');

        if ($rounding_interval) {
            $this->addConfigOption('rounding_enabled', true);
        } else {
            $this->addConfigOption('rounding_enabled', false);
        }
    }
}
