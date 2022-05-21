<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop related tasks data.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateDropRelatedTasks extends AngieModelMigration
{
    /**
     * Remove related tasks.
     */
    public function up()
    {
        $this->dropTable('related_tasks');
    }
}
