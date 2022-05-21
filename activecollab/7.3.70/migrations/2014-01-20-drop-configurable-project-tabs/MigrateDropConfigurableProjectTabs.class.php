<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove configurable project tabs.
 *
 * @package activeCollab.modules.system
 * @subpackage models
 */
class MigrateDropConfigurableProjectTabs extends AngieModelMigration
{
    /**
     * Remove configurable project tabs.
     */
    public function up()
    {
        $this->removeConfigOption('project_tabs');
    }
}
