<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Remove old calendar_config values and replace with new one.
 *
 * @package angie.migrations
 */
class MigratePersonalizedCalendarConfig extends AngieModelMigration
{
    /**
     * Upgrade the data.
     */
    public function up()
    {
        $old_config_option = 'calendar_config';
        $hidden_calendars = 'hidden_calendars';
        $hidden_projects_on_calendar = 'hidden_projects_on_calendar';

        $this->addConfigOption($hidden_calendars, false);
        $this->addConfigOption($hidden_projects_on_calendar, false);

        $config_option_values = $this->useTableForAlter('config_option_values');

        if ($rows = $this->execute('SELECT * FROM ' . $config_option_values->getName() . ' WHERE name = ? AND parent_type = ?', $old_config_option, 'User')) {
            foreach ($rows as $row) {
                $user_id = (int) isset($row['parent_id']) ? $row['parent_id'] : null;
                $value = $row['value'] ? unserialize($row['value']) : null;

                if ($user_id && $value && is_array($value)) {
                    $project_ids = [];
                    $calendar_ids = [];

                    foreach ($value as $type => $ids) {
                        if (is_foreachable($ids)) {
                            foreach ($ids as $id => $settings) {
                                $visible = (bool) isset($settings['visible']) ? $settings['visible'] : true;

                                if (!$visible) {
                                    if ($type == 'UserCalendar') {
                                        $calendar_ids[] = $id;
                                    } elseif ($type == 'Project') {
                                        $project_ids[] = $id;
                                    }
                                }
                            }
                        }
                    }

                    if (!empty($calendar_ids)) {
                        $this->execute("INSERT INTO {$config_option_values->getName()} (name,parent_type,parent_id,value) VALUES (?, ?, ?, ?)", $hidden_calendars, 'User', $user_id, serialize($calendar_ids));
                    }

                    if (!empty($project_ids)) {
                        $this->execute("INSERT INTO {$config_option_values->getName()} (name,parent_type,parent_id,value) VALUES (?, ?, ?, ?)", $hidden_projects_on_calendar, 'User', $user_id, serialize($project_ids));
                    }
                }
            }

            // remove old calendar config
            $this->execute("DELETE FROM {$config_option_values->getName()} WHERE name = ?", $old_config_option);
        }

        $this->removeConfigOption($old_config_option);
    }
}
