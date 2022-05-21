<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddMissingAvailabilityConfigOptionsForSomeSh extends AngieModelMigration
{
    public function up()
    {
        if (!AngieApplication::isOnDemand()) {
            if (!$this->getConfigOptionValue('availability_enabled')) {
                $this->addConfigOption('availability_enabled', true);
            }
            if (!$this->getConfigOptionValue('availability_enabled_lock')) {
                $this->addConfigOption('availability_enabled_lock', false);
            }
        }
    }
}
