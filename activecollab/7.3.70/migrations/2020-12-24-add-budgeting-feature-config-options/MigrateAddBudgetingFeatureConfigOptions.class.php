<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddBudgetingFeatureConfigOptions extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('budgeting_enabled', true);
        $this->addConfigOption('budgeting_enabled_lock', true);
    }
}
