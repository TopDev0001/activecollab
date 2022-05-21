<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateResetProjectFilterUpdateTimestamp extends AngieModelMigration
{
    public function up()
    {
        $options_to_fix = ['filter_client_projects', 'filter_label_projects', 'filter_category_projects'];

        [$config_options, $config_options_values] = $this->useTables('config_options', 'config_option_values');

        $this->execute("UPDATE $config_options SET updated_on = UTC_TIMESTAMP() WHERE name IN (?)", $options_to_fix);

        foreach ($options_to_fix as $option_to_fix) {
            if ($rows = $this->execute("SELECT parent_id, value FROM $config_options_values WHERE name = ? AND parent_type = ?", $option_to_fix, 'User')) {
                foreach ($rows as $row) {
                    $value = $row['value'] ? strtolower((string) unserialize($row['value'])) : null;

                    if (empty($value) || $value == 'any') {
                        $this->execute("DELETE FROM $config_options_values WHERE name = ? AND parent_type = ? AND parent_id = ?", $option_to_fix, 'User', $row['parent_id']);
                    }
                }
            }
        }
    }
}
