<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddProjectTimelineExportConfigOption extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('project_timeline_export', defined('IS_ON_DEMAND') && IS_ON_DEMAND);
    }
}
