<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Fix configuration option values.
 *
 * @package angie.migrations
 */
class MigrateFixConfigOptionValues extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        [$config_options, $config_option_values] = $this->useTables('config_options', 'config_option_values');

        if ($rows = $this->execute("SELECT id, name, value FROM $config_options WHERE value IS NOT NULL")) {
            foreach ($rows as $row) {
                if ($row['value'] === 'b:0;') {
                    continue; // skip real false
                }

                try {
                    $value = @unserialize($row['value']);

                    if ($value === false) {
                        $this->execute("UPDATE $config_options SET value = NULL WHERE id = ?", $row['id']);
                    }
                } catch (Throwable $e) {
                    $this->execute("DELETE FROM $config_options WHERE name = ?", $row['name']);
                    $this->execute("DELETE FROM $config_option_values WHERE name = ?", $row['name']);
                }
            }
        }

        if ($rows = $this->execute("SELECT name, parent_type, parent_id, value FROM $config_option_values WHERE value IS NOT NULL")) {
            foreach ($rows as $row) {
                if ($row['value'] === 'b:0;') {
                    continue; // skip real false
                }

                try {
                    $value = @unserialize($row['value']);

                    if ($value === false) {
                        $this->execute("UPDATE $config_option_values SET value = NULL WHERE name = ? AND parent_type = ? AND parent_id = ?", $row['name'], $row['parent_type'], $row['parent_id']);
                    }
                } catch (Throwable $e) {
                    $this->execute("DELETE FROM $config_options WHERE name = ?", $row['name']);
                    $this->execute("DELETE FROM $config_option_values WHERE name = ?", $row['name']);
                }
            }
        }

        $this->doneUsingTables();
    }
}
