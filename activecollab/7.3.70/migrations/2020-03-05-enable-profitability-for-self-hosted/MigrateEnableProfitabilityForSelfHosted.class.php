<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateEnableProfitabilityForSelfHosted extends AngieModelMigration
{
    public function up()
    {
        if (!AngieApplication::isOnDemand()) {
            $this->addConfigOption('profitability_enabled', true);
            $this->addConfigOption('profitability_enabled_lock', true);
        }
    }
}
