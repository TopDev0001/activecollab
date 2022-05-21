<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Set up multiple-assignees configuration option.
 *
 * @package activeCollab.modules.system
 * @subpackage migrations
 */
class MigrateMultipleAssigneesDefaults extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('multiple_assignees_for_milestones_and_tasks', (bool) $this->executeFirstCell('SELECT COUNT(*) FROM assignments'));
    }
}
