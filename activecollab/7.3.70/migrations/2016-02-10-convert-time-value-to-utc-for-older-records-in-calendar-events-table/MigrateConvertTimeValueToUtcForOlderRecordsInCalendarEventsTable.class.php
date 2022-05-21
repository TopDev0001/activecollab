<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateConvertTimeValueToUtcForOlderRecordsInCalendarEventsTable extends AngieModelMigration
{
    public function up()
    {
        [$calendar_events] = $this->useTables('calendar_events');

        if ($rows = $this->execute("SELECT id, starts_on, starts_on_time, ends_on FROM $calendar_events WHERE starts_on_time IS NOT NULL AND starts_on = ends_on")) {
            foreach ($rows as $row) {
                $date_time_value = $row['starts_on'] . ' ' . $row['starts_on_time'];

                // create datetime object based on system default timezone
                $date = new \DateTime($date_time_value, new \DateTimeZone($this->getConfigOptionValue('time_timezone')));

                // change timezone to utc
                $date->setTimezone(new DateTimeZone('UTC'));

                // update dates in old record
                $new_date = $date->format('Y-m-d');
                $new_time = $date->format('H:i:s');
                $this->execute("UPDATE $calendar_events SET starts_on = ?, ends_on = ?, starts_on_time = ? WHERE id = ?", $new_date, $new_date, $new_time, $row['id']);
            }
        }

        $this->doneUsingTables();
    }
}
