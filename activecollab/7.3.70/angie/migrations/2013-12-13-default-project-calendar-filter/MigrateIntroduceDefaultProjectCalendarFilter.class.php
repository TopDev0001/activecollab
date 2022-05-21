<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Introduce default project calendar filter.
 *
 * @package angie.migrations
 */
class MigrateIntroduceDefaultProjectCalendarFilter extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $this->setConfigOptionValue('default_project_calendar_filter', [
            'type' => 'everything_in_my_projects',
        ]);
    }
}
