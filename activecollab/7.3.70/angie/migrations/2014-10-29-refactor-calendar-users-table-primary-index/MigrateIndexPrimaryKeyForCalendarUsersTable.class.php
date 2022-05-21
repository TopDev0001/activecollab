<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Migrate index primary key for calendar users table.
 *
 * @package angie.migrations
 */
class MigrateIndexPrimaryKeyForCalendarUsersTable extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $calendar_users_table = $this->useTableForAlter('calendar_users');

        if ($rows = $this->execute("SELECT COUNT(*) AS 'count', calendar_id, user_id FROM {$calendar_users_table->getName()} GROUP BY calendar_id, user_id HAVING count > 1")) {
            $batch = new DBBatchInsert($calendar_users_table->getName(), ['user_id', 'calendar_id']);

            foreach ($rows as $row) {
                $this->execute("DELETE FROM {$calendar_users_table->getName()} WHERE calendar_id = ? AND user_id = ?", $row['calendar_id'], $row['user_id']);
                $batch->insert($row['user_id'], $row['calendar_id']);
            }

            $batch->done();
        }

        $calendar_users_table->dropIndex('calendar_id');
        $calendar_users_table->dropIndex('user_id');
        $calendar_users_table->addIndices(
            [
                new DBIndexPrimary(['user_id', 'calendar_id']),
            ]
        );
    }
}
