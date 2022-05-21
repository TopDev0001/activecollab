<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateRemoveProjectBudgetingSetup extends AngieModelMigration
{
    public function up()
    {
        $this->removeConfigOption('project_budgeting_setup');
        $this->removeConfigOption('project_budgeting_setup_pointer_seen');
        AngieApplication::memories()->forget('is_migration_for_budget_types_onboarding_executed');
    }
}
