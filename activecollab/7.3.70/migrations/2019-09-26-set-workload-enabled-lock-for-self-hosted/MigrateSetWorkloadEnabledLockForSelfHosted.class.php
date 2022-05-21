<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateSetWorkloadEnabledLockForSelfHosted extends AngieModelMigration
{
    public function up()
    {
        if (!AngieApplication::isOnDemand()) {
            $this->addConfigOption('workload_enabled_lock', false, true);
        }
    }
}
