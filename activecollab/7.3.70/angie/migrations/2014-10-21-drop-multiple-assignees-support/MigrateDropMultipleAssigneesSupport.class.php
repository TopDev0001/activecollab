<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop support for multiple assignees.
 *
 * @package angie.migrations
 */
class MigrateDropMultipleAssigneesSupport extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->dropTable('assignments');
        $this->removeConfigOption('multiple_assignees_for_milestones_and_tasks');
    }
}
