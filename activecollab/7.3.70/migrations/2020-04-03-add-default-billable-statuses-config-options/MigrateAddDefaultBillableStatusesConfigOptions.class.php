<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddDefaultBillableStatusesConfigOptions extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('default_tracking_objects_are_billable', true, true);
        $this->addConfigOption('default_members_can_change_billable', true, true);
        $this->addConfigOption('default_project_budget_type', 'pay_as_you_go', true);
    }
}
