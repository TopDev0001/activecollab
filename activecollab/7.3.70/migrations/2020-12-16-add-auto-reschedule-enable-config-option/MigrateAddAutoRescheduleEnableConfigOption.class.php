<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddAutoRescheduleEnableConfigOption extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('auto_reschedule_enabled', true);
        $this->addConfigOption('auto_reschedule_enabled_lock', true);
    }
}
