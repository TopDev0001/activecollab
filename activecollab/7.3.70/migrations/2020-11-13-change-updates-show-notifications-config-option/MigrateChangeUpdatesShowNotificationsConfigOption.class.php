<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateChangeUpdatesShowNotificationsConfigOption extends AngieModelMigration
{
    public function up()
    {
        $config_option_values = $this->useTableForAlter('config_option_values');
        $config_option = 'updates_show_notifications';
        $new_config_option = 'updates_hide_notifications';

        if ($rows = $this->execute("SELECT * FROM {$config_option_values->getName()} WHERE name = ?", $config_option)) {
            foreach ($rows as $row) {
                if (isset($row['parent_type']) && isset($row['parent_id']) && $row['parent_type'] && $row['parent_id']) {
                    $value = isset($row['value']) && $row['value'] ? (bool) unserialize($row['value']) : null;

                    if ($value !== null) {
                        $new_value = !$value;
                        $this->execute("UPDATE {$config_option_values->getName()} SET value = ?, name = ? WHERE name = ? AND parent_type = ? AND parent_id = ?", serialize($new_value), $new_config_option, $config_option, $row['parent_type'], $row['parent_id']);
                    }
                }
            }
        }

        $this->removeConfigOption($config_option);
        $this->addConfigOption($new_config_option, false);
    }
}
