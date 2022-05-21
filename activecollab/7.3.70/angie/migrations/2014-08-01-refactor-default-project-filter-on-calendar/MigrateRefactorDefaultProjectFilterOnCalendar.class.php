<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Refactor default project filter on calendar.
 *
 * @package angie.migrations
 */
class MigrateRefactorDefaultProjectFilterOnCalendar extends AngieModelMigration
{
    /**
     * Upgrade the data.
     */
    public function up()
    {
        $config_option_values = $this->useTableForAlter('config_option_values');

        $config_option = 'default_project_calendar_filter';

        if ($rows = $this->execute("SELECT * FROM {$config_option_values->getName()} WHERE name = ?", $config_option)) {
            foreach ($rows as $row) {
                if (isset($row['parent_type']) && isset($row['parent_id']) && $row['parent_type'] && $row['parent_id']) {
                    $value = isset($row['value']) && $row['value'] ? unserialize($row['value']) : null;

                    $type = (string) array_var($value, 'type');
                    $user_id = (int) array_var($value, 'value');

                    if ($type) {
                        $new_value = $type == 'user' ? $user_id : $type;
                        $this->execute("UPDATE {$config_option_values->getName()} SET value = ? WHERE name = ? AND parent_type = ? AND parent_id = ?", serialize($new_value), $config_option, $row['parent_type'], $row['parent_id']);
                    }
                }
            }
        }

        $this->setConfigOptionValue($config_option, 'everything_in_my_projects');
    }
}
