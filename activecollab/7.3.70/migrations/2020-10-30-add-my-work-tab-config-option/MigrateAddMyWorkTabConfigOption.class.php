<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddMyWorkTabConfigOption extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('my_work_selected_tab', 'tasks');
    }
}
