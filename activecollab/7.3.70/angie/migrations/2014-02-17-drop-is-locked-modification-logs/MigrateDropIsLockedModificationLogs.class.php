<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop is locked model migrations.
 *
 * @package angie.migrations
 */
class MigrateDropIsLockedModificationLogs extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$modification_logs, $modification_log_values] = $this->useTables('modification_logs', 'modification_log_values');

        $modification_log_ids = $this->executeFirstColumn("SELECT modification_id FROM $modification_log_values WHERE field = 'is_locked'");

        if ($modification_log_ids) {
            $this->transact(function () use ($modification_log_ids, $modification_logs, $modification_log_values) {
                $this->execute("DELETE FROM $modification_log_values WHERE field = 'is_locked'");

                $rows = $this->execute("SELECT modification_id, COUNT(*) AS 'modifications_count' FROM $modification_log_values WHERE modification_id IN (?) GROUP BY modification_id", $modification_log_ids);
                if ($rows) {
                    $to_drop = [];

                    foreach ($rows as $row) {
                        if (empty($row['modifications_count'])) {
                            $to_drop[] = $row['modification_id'];
                        }
                    }

                    if (count($to_drop)) {
                        $this->execute("DELETE FROM $modification_logs WHERE id IN (?)", $to_drop);
                    }
                }
            }, 'Droping modification logs');
        }

        $this->doneUsingTables();
    }
}
