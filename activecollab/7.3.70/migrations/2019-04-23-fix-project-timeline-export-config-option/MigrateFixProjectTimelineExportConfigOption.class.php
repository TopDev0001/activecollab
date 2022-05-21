<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateFixProjectTimelineExportConfigOption extends AngieModelMigration
{
    public function up()
    {
        $this->addConfigOption('project_timeline_export', true);
    }
}
