<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Initialize data that is needed for my tasks page.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateInitMyTasks extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('my_tasks_labels_filter', 'any');
        $this->addConfigOption('my_tasks_labels_filter_data');
    }
}
