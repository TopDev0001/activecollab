<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop task segments model.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateDropTaskSegments extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->dropTable('task_segments');
    }
}
