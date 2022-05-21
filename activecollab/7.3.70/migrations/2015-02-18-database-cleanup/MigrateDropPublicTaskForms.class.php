<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop public task forms model.
 *
 * @package ActiveCollab.migrations
 */
class MigrateDropPublicTaskForms extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->dropTable('public_task_forms');
    }
}
