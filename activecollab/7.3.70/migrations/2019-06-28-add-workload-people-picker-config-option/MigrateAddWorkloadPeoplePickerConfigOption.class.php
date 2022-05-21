<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddWorkloadPeoplePickerConfigOption extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('workload_people_picker', null);
    }
}
