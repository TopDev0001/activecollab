<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Retire effective_work_hours configuration option.
 *
 * @package angie.migrations
 */
class MigrateRetireEffectiveWorkHours extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->removeConfigOption('effective_work_hours');
    }
}
