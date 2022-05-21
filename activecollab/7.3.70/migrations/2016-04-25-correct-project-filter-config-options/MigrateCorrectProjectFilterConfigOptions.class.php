<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * @package ActiveCollab.migrations
 */
class MigrateCorrectProjectFilterConfigOptions extends AngieModelMigration
{
    public function up()
    {
        $config_options = $this->useTables('config_options')[0];
        $config_options_values = $this->useTables('config_option_values')[0];

        $this->execute("UPDATE $config_options SET value = ? WHERE name = ?", serialize(''), 'filter_client_projects');
        $this->execute("UPDATE $config_options SET value = ? WHERE name = ?", serialize(''), 'filter_label_projects');
        $this->execute("UPDATE $config_options SET value = ? WHERE name = ?", serialize(''), 'filter_category_projects');

        foreach (['filter_client_projects', 'filter_label_projects', 'filter_category_projects'] as $config_option_name) {
            if ($rows = $this->execute("SELECT parent_id, value FROM $config_options_values WHERE name = ? AND parent_type = ?", $config_option_name, 'User')) {
                foreach ($rows as $row) {
                    $value = strtolower(unserialize($row['value']));

                    if ($value == 'any') {
                        $this->execute("UPDATE $config_options_values SET value = ? WHERE name = ? AND parent_id = ? AND parent_type = ?", serialize(''), $config_option_name, $row['parent_id'], 'User');
                    }
                }
            }
        }
    }
}
