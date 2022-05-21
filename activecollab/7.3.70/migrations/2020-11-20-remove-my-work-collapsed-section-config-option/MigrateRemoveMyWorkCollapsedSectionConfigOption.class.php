<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateRemoveMyWorkCollapsedSectionConfigOption extends AngieModelMigration
{
    public function up()
    {
        $this->removeConfigOption('my_work_collapsed_sections');
    }
}
