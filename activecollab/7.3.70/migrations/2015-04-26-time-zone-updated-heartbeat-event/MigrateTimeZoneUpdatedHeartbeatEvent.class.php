<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrare time zone updated heartbeat event.
 *
 * @package ActiveCollab.migrations
 */
class MigrateTimeZoneUpdatedHeartbeatEvent extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $timezone = $this->getConfigOptionValue('time_timezone');

        if ($timezone != 'UTC') {
            $heartbeat_queue = $this->useTables('heartbeat_queue')[0];

            do {
                $event_hash = make_string(40);
            } while ($this->executeFirstCell("SELECT COUNT(id) AS 'row_count' FROM $heartbeat_queue WHERE hash = ?", $event_hash));

            $this->execute("INSERT INTO $heartbeat_queue (hash, json) VALUES (?, ?)", $event_hash, json_encode([
                'event' => 'ActiveCollab/ChangeTimeZone',
                'payload' => [
                    'old_value' => 'UTC',
                    'new_value' => $timezone,
                ],
                'timestamp' => time(),
            ]));
        }
    }
}
