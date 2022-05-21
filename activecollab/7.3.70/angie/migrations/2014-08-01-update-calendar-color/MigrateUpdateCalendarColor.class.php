<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Update calendar color add # in front of.
 *
 * @package angie.migrations
 */
class MigrateUpdateCalendarColor extends AngieModelMigration
{
    /**
     * Upgrade the data.
     */
    public function up()
    {
        $calendars_table = $this->useTableForAlter('calendars');

        $this->execute("UPDATE {$calendars_table->getName()} SET color = CONCAT('#', color)");
    }
}
