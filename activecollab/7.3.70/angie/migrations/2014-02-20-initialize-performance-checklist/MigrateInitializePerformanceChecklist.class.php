<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Initialize performance checklist.
 *
 * @package angie.migrations
 */
class MigrateInitializePerformanceChecklist extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->addConfigOption('control_tower_check_performance', true);
    }
}
