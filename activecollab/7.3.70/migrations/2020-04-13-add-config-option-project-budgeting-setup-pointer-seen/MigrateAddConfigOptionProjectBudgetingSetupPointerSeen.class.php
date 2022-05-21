<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddConfigOptionProjectBudgetingSetupPointerSeen extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('project_budgeting_setup_pointer_seen', false);
    }
}
