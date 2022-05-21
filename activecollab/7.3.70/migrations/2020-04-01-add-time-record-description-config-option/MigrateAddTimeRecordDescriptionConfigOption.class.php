<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddTimeRecordDescriptionConfigOption extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('time_record_description_expanded', false, true);
    }
}
