<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class MigrateAddArchivedOnToUsers extends AngieModelMigration
{
    public function up()
    {
        if (!in_array('field', DB::listTableIndexes('modification_log_values'))) {
            $this
                ->useTableForAlter('modification_log_values')
                   ->addIndex(new DBIndex('field'));
        }

        $users = $this->useTableForAlter('users');

        $users->addColumn(
            new DBDateTimeColumn('archived_on'),
            'original_is_archived'
        );

        if ($rows = DB::execute('SELECT `id` FROM `users` WHERE `is_archived` = ?', true)) {
            foreach ($rows as $row) {
                DB::execute(
                    'UPDATE `users` SET `archived_on` = ? WHERE `id` = ?',
                    $this->getUserArchivedOn($row['id']),
                    $row['id']
                );
            }
        }

        $users->addIndex(new DBIndex('archived_on'));
    }

    private function getUserArchivedOn($user_id)
    {
        if ($archived_on = $this->getArchivedOnFromModificationLogs($user_id)) {
            return $archived_on;
        }

        if ($archived_on = $this->getArchivedOnFromAccessLogs($user_id)) {
            return $archived_on;
        }

        if ($archived_on = $this->getArchivedOnFromActivityLogs($user_id)) {
            return $archived_on;
        }

        return $this->getOldestModificationLog();
    }

    private function getArchivedOnFromModificationLogs($user_id)
    {
        return DB::executeFirstCell(
            'SELECT `created_on`
                FROM `modification_logs`, `modification_log_values`
                WHERE `modification_logs`.`id` = `modification_log_values`.`modification_id`
                    AND `modification_logs`.parent_type IN (?)
                    AND modification_logs.parent_id = ?
                ORDER BY `created_on` DESC
                LIMIT 0, 1',
            [
                Owner::class,
                Member::class,
                Client::class,
            ],
            $user_id
        );
    }

    private function getArchivedOnFromAccessLogs($user_id)
    {
        return DB::executeFirstCell(
            'SELECT `accessed_on`
                FROM `access_logs`
                WHERE `accessed_by_id` = ?
                ORDER BY `accessed_on` DESC
                LIMIT 0, 1',
            $user_id
        );
    }

    private function getArchivedOnFromActivityLogs($user_id)
    {
        return DB::executeFirstCell(
            'SELECT `created_on`
                FROM `activity_logs`
                WHERE `created_by_id` = ?
                ORDER BY `created_on` DESC
                LIMIT 0, 1',
            $user_id
        );
    }

    private $oldest_modification_log = false;

    private function getOldestModificationLog()
    {
        if ($this->oldest_modification_log === false) {
            $this->oldest_modification_log = DB::executeFirstCell('SELECT MIN(`created_on`) FROM `modification_logs`');

            if (empty($this->oldest_modification_log)) {
                $this->oldest_modification_log = new DateTimeValue();
            }
        }

        return $this->oldest_modification_log;
    }
}
