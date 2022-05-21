<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop is_locked field.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateDropIsLockedField extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->useTableForAlter('project_objects')->dropColumn('is_locked');
        $this->useTableForAlter('project_requests')->dropColumn('is_locked');

        $this->doneUsingTables();
    }
}
