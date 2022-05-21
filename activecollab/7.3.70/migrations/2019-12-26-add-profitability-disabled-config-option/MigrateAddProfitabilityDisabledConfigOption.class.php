<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddProfitabilityDisabledConfigOption extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('profitability_enabled', false);
        $this->addConfigOption('profitability_enabled_lock', true);
    }
}
