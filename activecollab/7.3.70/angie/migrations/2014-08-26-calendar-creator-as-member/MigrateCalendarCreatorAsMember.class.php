<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate set calendar creator user as calendar member user.
 *
 * @package angie.migrations
 */
class MigrateCalendarCreatorAsMember extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $calendars_table = $this->useTableForAlter('calendars');
        $calendar_users_table = $this->useTableForAlter('calendar_users');

        if ($rows = $this->execute("SELECT * FROM {$calendars_table->getName()}")) {
            foreach ($rows as $row) {
                if (isset($row['created_by_id']) && $row['created_by_id'] && isset($row['id']) && $row['id']) {
                    $this->execute("INSERT INTO {$calendar_users_table->getName()} (user_id, calendar_id) VALUES (?, ?)", $row['created_by_id'], $row['id']);
                }
            }
        }
    }
}
