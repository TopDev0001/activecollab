<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Drop share_type and share_can_add_event fields from calendar table.
 *
 * @package angie.migrations
 */
class MigrateDropCalendarShareFields extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        $share_with_team_and_subcontractors = 'members_and_subcontractors';
        $share_with_managers_only = 'managers';
        $share_with_everyone = 'everyone';
        $share_with_members_only = 'members';
        $share_with_admin_only = 'admins';

        $calendars_table = $this->useTableForAlter('calendars');
        [$calendar_users_table, $users_table] = $this->useTables('calendar_users', 'users');

        if ($calendars = $this->execute("SELECT id, share_type, created_by_id FROM {$calendars_table->getName()} WHERE share_type IS NOT NULL AND share_type NOT IN (?)", ['dont_share', 'selected'])) {
            $subcontractor_ids = [];
            $manager_ids = [];
            $member_ids = [];
            $admin_ids = [];
            $everyone_ids = [];

            foreach ($this->execute("SELECT id, type, raw_additional_properties FROM $users_table") as $row) {
                $additional_properties = empty($row['raw_additional_properties']) ? [] : unserialize($row['raw_additional_properties']);

                $custom_permissions = [];

                if (isset($additional_properties['custom_permissions']) && is_array($additional_properties['custom_permissions'])) {
                    $custom_permissions = $additional_properties['custom_permissions'];
                }

                // Subcontractor
                if ($row['type'] == 'Subcontractor') {
                    $subcontractor_ids[] = $row['id'];
                }

                // Manager
                if (in_array('can_manage_projects', $custom_permissions) || in_array('can_manage_finances', $custom_permissions)) {
                    $manager_ids[] = $row['id'];
                }

                // Member
                if ($row['type'] == 'Member') {
                    $member_ids[] = $row['id'];
                }

                // Administrator
                if ($row['type'] == 'Owner' || ($row['type'] == 'Member' && in_array('can_manage_settings', $custom_permissions))) {
                    $admin_ids[] = $row['id'];
                }

                // Everyone
                $everyone_ids[] = $row['id'];
            }

            foreach ($calendars as $calendar) {
                $share_type = $calendar['share_type'];

                if (empty($share_type)) {
                    continue;
                }

                switch ($share_type) {
                    case $share_with_team_and_subcontractors:
                        $user_ids = array_merge($subcontractor_ids, $member_ids);
                        break;
                    case $share_with_managers_only:
                        $user_ids = $manager_ids;
                        break;
                    case $share_with_everyone:
                        $user_ids = $everyone_ids;
                        break;
                    case $share_with_members_only:
                        $user_ids = $member_ids;
                        break;
                    case $share_with_admin_only:
                        $user_ids = $admin_ids;
                        break;
                    default:
                        $user_ids = [];
                        break;
                }

                foreach ($user_ids as $user_id) {
                    if (!$this->executeFirstCell("SELECT COUNT(*) FROM $calendar_users_table WHERE user_id = ? AND calendar_id = ?", $user_id, $calendar['id'])) {
                        $this->execute("INSERT INTO $calendar_users_table (user_id, calendar_id) VALUES (?, ?)", $user_id, $calendar['id']);
                    }
                }
            }

            $calendars_table->dropColumn('share_can_add_events');
            $calendars_table->dropColumn('share_type');

            $this->doneUsingTables();
        }
    }
}
