<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateRemoveWorkloadGotItConfigOption extends AngieModelMigration
{
    public function up()
    {
        ConfigOptions::removeOption('workload_got_it');
    }
}
